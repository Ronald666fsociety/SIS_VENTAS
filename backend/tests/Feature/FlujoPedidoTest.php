<?php

namespace Tests\Feature;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Models\Auditoria;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

class FlujoPedidoTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararDiscos();
        $this->configuracionPago();
    }

    private function crearPedido(int $cantidad = 3, float $precio = 10, int $stock = 50): array
    {
        $this->zonaCentro();
        $producto = $this->producto($precio, $stock);
        $cliente = $this->como($this->usuario());
        $resp = $this->postJson('/api/v1/pedidos', $this->cuerpoPedido($producto, $cantidad));

        return [$resp, $producto, $cliente];
    }

    private function subirComprobante(int $pedidoId, ?string $nro = null)
    {
        return $this->post("/api/v1/pedidos/{$pedidoId}/pago/comprobante", [
            'comprobante' => UploadedFile::fake()->image('comp.jpg', 400, 600),
            'nro_transaccion' => $nro,
        ], ['Accept' => 'application/json']);
    }

    public function test_crear_pedido_descuenta_stock_y_copia_precios(): void
    {
        [$resp, $producto] = $this->crearPedido(3, 10);

        $resp->assertCreated()
            ->assertJsonPath('data.estado', 'pendiente_pago')
            ->assertJsonPath('data.subtotal', '30.00')
            ->assertJsonPath('data.costo_envio', '8.00')
            ->assertJsonPath('data.total', '38.00')
            ->assertJsonPath('data.pago.estado', 'pendiente')
            ->assertJsonMissingPath('data.codigo_entrega');

        $this->assertMatchesRegularExpression('/^PED-\d{4}-\d{6}$/', $resp->json('data.codigo'));
        $this->assertSame(47, $producto->fresh()->stock);

        // Cambiar el precio después no altera el pedido histórico
        $producto->update(['precio' => 99]);
        $this->assertSame('10.00', Pedido::first()->items->first()->precio_unitario);
    }

    public function test_pedido_fuera_de_zona_se_rechaza_sin_tocar_stock(): void
    {
        $this->zonaCentro();
        $p = $this->producto(10, 20);
        $this->como($this->usuario());

        $this->postJson('/api/v1/pedidos', $this->cuerpoPedido($p, 3, ['lat' => -10.0, 'lng' => -60.0]))
            ->assertStatus(422)->assertJsonValidationErrors('direccion');

        $this->assertSame(20, $p->fresh()->stock);
        $this->assertSame(0, Pedido::count());
    }

    public function test_stock_insuficiente_y_pedido_minimo(): void
    {
        $this->zonaCentro();
        $p = $this->producto(10, 2);
        $this->como($this->usuario());

        $this->postJson('/api/v1/pedidos', $this->cuerpoPedido($p, 5))->assertStatus(422)->assertJsonValidationErrors('items');
        $this->postJson('/api/v1/pedidos', $this->cuerpoPedido($p, 1))->assertStatus(422)->assertJsonValidationErrors('items'); // mínimo 20
        $this->assertSame(2, $p->fresh()->stock);
    }

    public function test_flujo_completo_hasta_la_entrega(): void
    {
        [$resp, , $cliente] = $this->crearPedido();
        $pedidoId = $resp->json('data.id');

        $this->subirComprobante($pedidoId, 'TX-1')->assertOk()->assertJsonPath('data.estado', 'en_revision');
        $this->assertSame(EstadoPedido::PagoEnRevision, Pedido::find($pedidoId)->estado);

        // Operador aprueba
        $operador = $this->como($this->usuario(Rol::Operador));
        $pagoId = Pago::first()->id;
        $this->getJson('/api/v1/admin/pagos')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/admin/pagos/{$pagoId}/aprobar")->assertOk()->assertJsonPath('data.estado', 'verificado');
        $this->assertSame(EstadoPedido::Preparando, Pedido::find($pedidoId)->estado);
        $this->assertDatabaseHas('auditoria', ['accion' => 'pago.aprobado', 'user_id' => $operador->id, 'entidad_id' => $pagoId]);

        // Operador: preparar → listo → asignar repartidor
        $repartidor = $this->usuario(Rol::Repartidor);
        $this->putJson("/api/v1/admin/pedidos/{$pedidoId}/estado", ['estado' => 'listo_para_envio'])->assertOk();
        $this->putJson("/api/v1/admin/pedidos/{$pedidoId}/repartidor", ['repartidor_id' => $repartidor->id])->assertOk();

        // Repartidor sale en camino
        $this->como($repartidor);
        $this->getJson('/api/v1/repartidor/pedidos')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/repartidor/pedidos/{$pedidoId}/en-camino")->assertOk()->assertJsonPath('data.estado', 'en_camino');

        // Cliente ve el código de entrega ahora que va en camino
        $this->como($cliente);
        $codigo = $this->getJson("/api/v1/pedidos/{$pedidoId}")->assertOk()->json('data.codigo_entrega');
        $this->assertMatchesRegularExpression('/^\d{4}$/', $codigo);

        // Entrega: código incorrecto falla, correcto con foto funciona
        $this->como($repartidor);
        $foto = fn () => UploadedFile::fake()->image('entrega.jpg');
        $malo = $codigo === '0000' ? '1111' : '0000';
        $this->post("/api/v1/repartidor/pedidos/{$pedidoId}/entregar", ['codigo' => $malo, 'foto' => $foto()], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors('codigo');
        $this->post("/api/v1/repartidor/pedidos/{$pedidoId}/entregar", ['codigo' => $codigo, 'foto' => $foto()], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.estado', 'entregado');

        $pedido = Pedido::find($pedidoId);
        $this->assertNotNull($pedido->entregado_en);
        $this->assertNotNull($pedido->foto_entrega_url);
        $this->assertGreaterThanOrEqual(5, $pedido->estados()->count());
    }

    public function test_tres_rechazos_cancelan_y_devuelven_stock(): void
    {
        [$resp, $producto] = $this->crearPedido(3);
        $pedidoId = $resp->json('data.id');
        $cliente = User::find(Pedido::first()->user_id);

        for ($i = 1; $i <= 3; $i++) {
            $this->como($cliente);
            $this->subirComprobante($pedidoId, "TX-$i")->assertOk();

            $this->como($this->usuario(Rol::Operador));
            $this->postJson('/api/v1/admin/pagos/'.Pago::first()->id.'/rechazar', ['motivo' => 'Comprobante ilegible'])->assertOk();

            $esperado = $i < 3 ? EstadoPedido::PendientePago : EstadoPedido::Cancelado;
            $this->assertSame($esperado, Pedido::find($pedidoId)->estado, "intento $i");
        }

        $this->assertSame(50, $producto->fresh()->stock);
        $this->como($cliente);
        $this->subirComprobante($pedidoId)->assertStatus(422);
    }

    public function test_numero_de_transaccion_duplicado(): void
    {
        [$resp1] = $this->crearPedido();
        $this->subirComprobante($resp1->json('data.id'), 'DUP-1')->assertOk();

        $p = $this->producto(10, 50);
        $otro = $this->como($this->usuario());
        $id2 = $this->postJson('/api/v1/pedidos', $this->cuerpoPedido($p, 3))->json('data.id');
        $this->subirComprobante($id2, 'DUP-1')->assertStatus(422)->assertJsonValidationErrors('nro_transaccion');
    }

    public function test_archivo_que_no_es_imagen_se_rechaza(): void
    {
        [$resp] = $this->crearPedido();
        $this->post('/api/v1/pedidos/'.$resp->json('data.id').'/pago/comprobante', [
            'comprobante' => UploadedFile::fake()->createWithContent('x.jpg', '<?php echo 1; ?>'),
        ], ['Accept' => 'application/json'])->assertStatus(422);
        $this->assertSame(EstadoPago::Pendiente, Pago::first()->estado);
    }

    public function test_cliente_cancela_y_stock_vuelve(): void
    {
        [$resp, $producto] = $this->crearPedido(3);
        $this->postJson('/api/v1/pedidos/'.$resp->json('data.id').'/cancelar')->assertOk()->assertJsonPath('data.estado', 'cancelado');
        $this->assertSame(50, $producto->fresh()->stock);
    }

    public function test_cancelacion_automatica_por_falta_de_pago(): void
    {
        [$resp, $producto] = $this->crearPedido(3);
        $this->assertSame(0, $this->artisan('pedidos:cancelar-vencidos')->run());
        $this->assertSame(EstadoPedido::PendientePago, Pedido::first()->estado);

        $this->travel(config('tienda.plazo_pago_minutos') + 1)->minutes();
        $this->artisan('pedidos:cancelar-vencidos')->assertSuccessful();

        $this->assertSame(EstadoPedido::Cancelado, Pedido::first()->estado);
        $this->assertSame(50, $producto->fresh()->stock);
    }

    public function test_un_cliente_no_ve_pedidos_de_otro(): void
    {
        [$resp] = $this->crearPedido();
        $this->como($this->usuario());
        $this->getJson('/api/v1/pedidos/'.$resp->json('data.id'))->assertForbidden();
        $this->postJson('/api/v1/pedidos/'.$resp->json('data.id').'/cancelar')->assertForbidden();
    }

    public function test_seguimiento_en_tiempo_real_y_eta_repartidor(): void
    {
        [$resp, , $cliente] = $this->crearPedido();
        $pedidoId = $resp->json('data.id');
        $this->subirComprobante($pedidoId, 'TX-GPS')->assertOk();

        $this->como($this->usuario(Rol::Operador));
        $this->postJson('/api/v1/admin/pagos/'.Pago::first()->id.'/aprobar')->assertOk();
        $repartidor = $this->usuario(Rol::Repartidor);
        $this->putJson("/api/v1/admin/pedidos/{$pedidoId}/estado", ['estado' => 'listo_para_envio'])->assertOk();
        $this->putJson("/api/v1/admin/pedidos/{$pedidoId}/repartidor", ['repartidor_id' => $repartidor->id])->assertOk();

        $this->como($repartidor);
        // Sale en camino a ~2 km del punto del cliente (-17.784, -63.183)
        $this->postJson("/api/v1/repartidor/pedidos/{$pedidoId}/en-camino", [
            'lat' => -17.802,
            'lng' => -63.183,
        ])->assertOk();

        $this->como($cliente);
        $ver1 = $this->getJson("/api/v1/pedidos/{$pedidoId}")->assertOk();
        $dist1 = $ver1->json('data.repartidor_ubicacion.distancia_km');
        $eta1 = $ver1->json('data.repartidor_ubicacion.tiempo_estimado_min');
        $this->assertGreaterThan(1.5, $dist1);
        $this->assertGreaterThan(2, $eta1);

        // El repartidor avanza a ~200m del cliente
        $this->como($repartidor);
        $this->postJson("/api/v1/repartidor/pedidos/{$pedidoId}/ubicacion", [
            'lat' => -17.786,
            'lng' => -63.183,
        ])->assertOk();

        $this->como($cliente);
        $ver2 = $this->getJson("/api/v1/pedidos/{$pedidoId}")->assertOk();
        $this->assertLessThan($dist1, $ver2->json('data.repartidor_ubicacion.distancia_km'));
        $this->assertLessThan($eta1, $ver2->json('data.repartidor_ubicacion.tiempo_estimado_min'));
    }

    public function test_envios_a_toda_bolivia(): void
    {
        $this->como($this->usuario(Rol::Admin));
        $this->postJson('/api/v1/admin/zonas', [
            'nombre' => 'Toda Bolivia (Nacional)',
            'costo_envio' => 25,
            'pedido_minimo' => 30,
            'centro_lat' => -17.3935,
            'centro_lng' => -66.1570,
            'radio_km' => 1200,
            'activa' => true,
        ])->assertCreated();

        // La Paz y Tarija deben estar cubiertas
        $this->postJson('/api/v1/zonas/validar', ['lat' => -16.4897, 'lng' => -68.1193])
            ->assertOk()->assertJsonPath('cubre', true)->assertJsonPath('costo_envio', '25.00');
        $this->postJson('/api/v1/zonas/validar', ['lat' => -21.5355, 'lng' => -64.7296])
            ->assertOk()->assertJsonPath('cubre', true)->assertJsonPath('zona.nombre', 'Toda Bolivia (Nacional)');
    }

    public function test_recuperar_y_restablecer_contrasena(): void
    {
        $cliente = $this->usuario();

        $rec = $this->postJson('/api/v1/auth/recuperar', [
            'telefono' => $cliente->telefono,
        ])->assertOk();

        $codigo = $rec->json('codigo_dev');
        $this->assertNotEmpty($codigo);

        $this->postJson('/api/v1/auth/restablecer', [
            'telefono' => $cliente->telefono,
            'codigo' => $codigo,
            'password' => 'nuevaClave99',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'telefono' => $cliente->telefono,
            'password' => 'nuevaClave99',
            'app' => 'cliente',
        ])->assertOk()->assertJsonStructure(['token', 'usuario']);
    }

    public function test_efectivo_comprobante_pdf_calificacion_favoritos_y_gastos(): void
    {
        $this->zonaCentro();
        $producto = $this->producto(25, 20);
        $cliente = $this->como($this->usuario());

        // Favoritos toggle y listado.
        $this->postJson("/api/v1/favoritos/{$producto->id}")
            ->assertOk()
            ->assertJsonPath('favorito', true);
        $this->getJson('/api/v1/favoritos')
            ->assertOk()
            ->assertJsonPath('ids.0', $producto->id);

        // Pedido en efectivo: arranca en preparando con pago efectivo pendiente.
        $cuerpo = $this->cuerpoPedido($producto, 2);
        $cuerpo['metodo_pago'] = 'efectivo';
        $creado = $this->postJson('/api/v1/pedidos', $cuerpo)
            ->assertCreated()
            ->assertJsonPath('data.estado', EstadoPedido::Preparando->value)
            ->assertJsonPath('data.pago.metodo', 'efectivo');

        $pedidoId = (int) $creado->json('data.id');

        // Descarga del comprobante / nota de venta en PDF.
        $pdf = $this->get("/api/v1/pedidos/{$pedidoId}/comprobante-pdf");
        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));

        // No permite calificar antes de ser entregado.
        $this->postJson("/api/v1/pedidos/{$pedidoId}/calificar", ['calificacion' => 5])
            ->assertUnprocessable();

        // Operador prepara, asigna repartidor y éste entrega -> verifica pago automáticamente.
        $rep = $this->usuario(Rol::Repartidor, ['telefono' => '+59171112233']);
        $this->como($this->usuario(Rol::Admin, ['telefono' => '+59170001122']));
        $this->putJson("/api/v1/admin/pedidos/{$pedidoId}/estado", ['estado' => 'listo_para_envio'])->assertOk();
        $this->putJson("/api/v1/admin/pedidos/{$pedidoId}/repartidor", ['repartidor_id' => $rep->id])->assertOk();

        $this->como($rep);
        $this->postJson("/api/v1/repartidor/pedidos/{$pedidoId}/en-camino")->assertOk();

        $this->como($cliente);
        $codigo = $this->getJson("/api/v1/pedidos/{$pedidoId}")->assertOk()->json('data.codigo_entrega');

        $this->como($rep);
        $this->post("/api/v1/repartidor/pedidos/{$pedidoId}/entregar", [
            'codigo' => $codigo,
            'foto' => UploadedFile::fake()->image('entrega.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.estado', EstadoPedido::Entregado->value);

        $this->assertSame(EstadoPago::Verificado, Pago::where('pedido_id', $pedidoId)->first()->estado);

        // Cliente ve el pago verificado, califica el pedido y consulta su historial de gastos.
        $this->como($cliente);
        $this->getJson("/api/v1/pedidos/{$pedidoId}")
            ->assertOk()
            ->assertJsonPath('data.pago.estado', EstadoPago::Verificado->value);
        $this->postJson("/api/v1/pedidos/{$pedidoId}/calificar", [
            'calificacion' => 5,
            'comentario' => 'Excelente atención y puntualidad',
        ])->assertOk()->assertJsonPath('data.calificacion', 5);

        $this->getJson('/api/v1/perfil/gastos')
            ->assertOk()
            ->assertJsonPath('pedidos_completados', 1);
    }
}
