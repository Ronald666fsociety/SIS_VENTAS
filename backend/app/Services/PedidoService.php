<?php

namespace App\Services;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Models\ConfiguracionPago;
use App\Models\Direccion;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\PedidoEstado;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoService
{
    public function __construct(
        private ZonaService $zonas,
        private NotificadorService $notificador,
        private ImagenService $imagenes,
        private AuditoriaService $auditoria,
    ) {
    }

    /**
     * Crea el pedido en una transacción: bloquea productos, descuenta stock,
     * copia precios y dirección, genera códigos y crea el pago pendiente.
     */
    public function crear(User $cliente, array $data): Pedido
    {
        return DB::transaction(function () use ($cliente, $data) {
            // 1) Dirección (guardada del cliente o enviada en línea)
            if (! empty($data['direccion_id'])) {
                $dir = Direccion::where('user_id', $cliente->id)->find($data['direccion_id']);
                if (! $dir) {
                    throw ValidationException::withMessages(['direccion_id' => 'La dirección no existe.']);
                }
                $direccion = [
                    'direccion_id' => $dir->id,
                    'direccion_texto' => $dir->direccion,
                    'referencia' => $dir->referencia,
                    'lat' => $dir->lat,
                    'lng' => $dir->lng,
                ];
            } else {
                $direccion = [
                    'direccion_id' => null,
                    'direccion_texto' => $data['direccion_texto'],
                    'referencia' => $data['referencia'] ?? null,
                    'lat' => (float) $data['lat'],
                    'lng' => (float) $data['lng'],
                ];
            }

            // 2) Zona de envío
            $zona = $this->zonas->resolver($direccion['lat'], $direccion['lng']);
            if (! $zona) {
                throw ValidationException::withMessages(['direccion' => 'Aún no llegamos a esta zona.']);
            }

            // 3) Productos bloqueados (orden por id para evitar deadlocks)
            $cantidades = collect($data['items'])
                ->groupBy('producto_id')
                ->map(fn ($filas) => (int) $filas->sum('cantidad'));

            $productos = Producto::query()
                ->whereIn('id', $cantidades->keys())
                ->where('activo', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = '0.00';
            $lineas = [];
            foreach ($cantidades as $productoId => $cantidad) {
                $p = $productos->get($productoId);
                if (! $p) {
                    throw ValidationException::withMessages(['items' => "El producto #{$productoId} no está disponible."]);
                }
                if ($p->stock < $cantidad) {
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente de {$p->nombre} (quedan {$p->stock}).",
                    ]);
                }
                $precio = $p->precioFinal();
                $lineaSubtotal = bcmul($precio, (string) $cantidad, 2);
                $subtotal = bcadd($subtotal, $lineaSubtotal, 2);
                $lineas[] = [
                    'producto' => $p,
                    'producto_id' => $p->id,
                    'nombre_producto' => $p->nombre,
                    'precio_unitario' => $precio,
                    'cantidad' => $cantidad,
                    'subtotal' => $lineaSubtotal,
                ];
            }

            if (bccomp($subtotal, (string) $zona->pedido_minimo, 2) < 0) {
                throw ValidationException::withMessages([
                    'items' => 'El pedido mínimo para tu zona es Bs '.number_format((float) $zona->pedido_minimo, 2).'.',
                ]);
            }

            $envio = (string) $zona->costo_envio;
            $total = bcadd($subtotal, $envio, 2);

            $metodoPago = ($data['metodo_pago'] ?? 'qr') === 'efectivo' ? 'efectivo' : 'qr';
            if ($metodoPago === 'efectivo') {
                $confPago = ConfiguracionPago::actual();
                if ($confPago && $confPago->aceptar_efectivo === false) {
                    throw ValidationException::withMessages([
                        'metodo_pago' => 'El pago en efectivo contra entrega no está habilitado actualmente.',
                    ]);
                }
            }

            $estadoInicial = $metodoPago === 'efectivo'
                ? EstadoPedido::Preparando
                : EstadoPedido::PendientePago;

            // 4) Pedido
            $pedido = Pedido::create($direccion + [
                'codigo' => 'TMP'.bin2hex(random_bytes(6)),
                'user_id' => $cliente->id,
                'zona_id' => $zona->id,
                'subtotal' => $subtotal,
                'costo_envio' => $envio,
                'descuento' => '0.00',
                'total' => $total,
                'estado' => $estadoInicial,
                'notas_cliente' => $data['notas_cliente'] ?? null,
                'codigo_entrega' => str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT),
            ]);
            $pedido->update(['codigo' => sprintf('PED-%s-%06d', now()->year, $pedido->id)]);

            // 5) Items + stock
            foreach ($lineas as $l) {
                $pedido->items()->create(collect($l)->except('producto')->all());
                $l['producto']->decrement('stock', $l['cantidad']);
            }

            // 6) Pago pendiente + línea de tiempo
            Pago::create([
                'pedido_id' => $pedido->id,
                'metodo' => $metodoPago,
                'monto' => $total,
                'estado' => EstadoPago::Pendiente,
            ]);
            if ($metodoPago === 'efectivo') {
                $this->registrarEstado($pedido, EstadoPedido::Preparando, $cliente, 'Pedido confirmado · Pago en efectivo contra entrega');
                $this->notificador->aPersonal(
                    [Rol::Operador, Rol::Admin],
                    'Nuevo pedido contra entrega',
                    "Pedido {$pedido->codigo} · Efectivo Bs {$total}",
                    $pedido,
                );
            } else {
                $this->registrarEstado($pedido, EstadoPedido::PendientePago, $cliente, 'Pedido creado');
            }

            return $pedido->load(['items', 'pago', 'estados', 'zona']);
        });
    }

    /**
     * Cambia el estado, registra la línea de tiempo y avisa al cliente.
     * $validar=false se usa en flujos internos del sistema (pagos, vencimientos).
     */
    public function cambiarEstado(
        Pedido $pedido,
        EstadoPedido $nuevo,
        ?User $actor = null,
        ?string $nota = null,
        bool $validar = true,
        array $extra = [],
        bool $notificar = true,
    ): Pedido {
        return DB::transaction(function () use ($pedido, $nuevo, $actor, $nota, $validar, $extra, $notificar) {
            /** @var Pedido $p */
            $p = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);

            if ($validar && ! $p->estado->puedePasarA($nuevo)) {
                throw ValidationException::withMessages([
                    'estado' => "No se puede pasar de «{$p->estado->etiqueta()}» a «{$nuevo->etiqueta()}».",
                ]);
            }
            if ($nuevo === EstadoPedido::EnCamino && ! $p->repartidor_id) {
                throw ValidationException::withMessages(['repartidor' => 'Asigna un repartidor antes de enviar el pedido.']);
            }

            $p->fill($extra);
            $p->estado = $nuevo;
            if ($nuevo === EstadoPedido::Entregado) {
                $p->entregado_en = now();
                $pago = $p->pago;
                if ($pago && $pago->metodo === 'efectivo' && $pago->estado === EstadoPago::Pendiente) {
                    $pago->update([
                        'estado' => EstadoPago::Verificado,
                        'verificado_por' => $actor?->id,
                        'verificado_en' => now(),
                    ]);
                }
            }
            $p->save();

            if ($nuevo === EstadoPedido::Cancelado) {
                $this->devolverStock($p);
                $pago = $p->pago;
                if ($pago && $pago->estado !== EstadoPago::Verificado) {
                    $pago->update([
                        'estado' => EstadoPago::Rechazado,
                        'motivo_rechazo' => $pago->motivo_rechazo ?: 'Pedido cancelado',
                    ]);
                }
            }

            $this->registrarEstado($p, $nuevo, $actor, $nota);

            if ($notificar) {
                $this->avisarCliente($p, $nuevo, $nota);
            }

            $pedido->setRawAttributes($p->getAttributes(), true);

            return $p;
        });
    }

    public function cancelarPorCliente(Pedido $pedido, User $cliente): Pedido
    {
        if (! $pedido->estado->cancelablePorCliente()) {
            throw ValidationException::withMessages([
                'estado' => 'Este pedido ya no se puede cancelar porque el pago fue verificado.',
            ]);
        }

        return $this->cambiarEstado($pedido, EstadoPedido::Cancelado, $cliente, 'Cancelado por el cliente');
    }

    public function asignarRepartidor(Pedido $pedido, int $repartidorId, User $actor): Pedido
    {
        $repartidor = User::query()->where('rol', Rol::Repartidor->value)->where('activo', true)->find($repartidorId);
        if (! $repartidor) {
            throw ValidationException::withMessages(['repartidor_id' => 'El repartidor no existe o está inactivo.']);
        }
        if ($pedido->estado->esFinal()) {
            throw ValidationException::withMessages(['estado' => 'El pedido ya está cerrado.']);
        }

        $pedido->update(['repartidor_id' => $repartidor->id]);
        $this->registrarEstado($pedido, $pedido->estado, $actor, "Repartidor asignado: {$repartidor->nombre}");
        $this->notificador->aUsuario($repartidor, 'Nuevo pedido asignado', "Pedido {$pedido->codigo}", $pedido);
        $this->auditoria->registrar($actor, 'pedido.repartidor_asignado', 'pedido', $pedido->id, ['repartidor_id' => $repartidor->id]);

        return $pedido->fresh();
    }

    public function marcarEnCamino(Pedido $pedido, User $repartidor, ?float $lat = null, ?float $lng = null): Pedido
    {
        if ($pedido->estado !== EstadoPedido::ListoParaEnvio) {
            throw ValidationException::withMessages(['estado' => 'El pedido aún no está listo para envío.']);
        }

        $extra = [];
        if ($lat !== null && $lng !== null) {
            $extra = [
                'repartidor_lat' => $lat,
                'repartidor_lng' => $lng,
                'repartidor_ubicacion_at' => now(),
            ];
        }

        return $this->cambiarEstado($pedido, EstadoPedido::EnCamino, $repartidor, 'Repartidor salió en camino', extra: $extra);
    }

    public function actualizarUbicacionRepartidor(Pedido $pedido, float $lat, float $lng): Pedido
    {
        if ($pedido->estado !== EstadoPedido::EnCamino) {
            throw ValidationException::withMessages(['estado' => 'Solo se puede actualizar ubicación cuando el pedido está en camino.']);
        }

        $pedido->update([
            'repartidor_lat' => $lat,
            'repartidor_lng' => $lng,
            'repartidor_ubicacion_at' => now(),
        ]);

        return $pedido->fresh();
    }

    public function entregar(Pedido $pedido, User $repartidor, string $codigo, UploadedFile $foto): Pedido
    {
        if ($pedido->estado !== EstadoPedido::EnCamino) {
            throw ValidationException::withMessages(['estado' => 'El pedido no está en camino.']);
        }
        if (! hash_equals((string) $pedido->codigo_entrega, $codigo)) {
            throw ValidationException::withMessages(['codigo' => 'El código de entrega no coincide.']);
        }

        $ruta = $this->imagenes->guardar($foto, config('tienda.disco_privado'), 'entregas/'.$pedido->id);

        return $this->cambiarEstado(
            $pedido,
            EstadoPedido::Entregado,
            $repartidor,
            'Entregado con código y foto',
            extra: ['foto_entrega_url' => $ruta],
        );
    }

    /** Cancela pedidos sin pago dentro del plazo y devuelve stock. Devuelve la cantidad cancelada. */
    public function cancelarVencidos(): int
    {
        $limite = now()->subMinutes(config('tienda.plazo_pago_minutos'));
        $cancelados = 0;

        Pedido::query()
            ->where('estado', EstadoPedido::PendientePago->value)
            ->where('created_at', '<', $limite)
            ->orderBy('id')
            ->each(function (Pedido $p) use (&$cancelados) {
                $this->cambiarEstado($p, EstadoPedido::Cancelado, null, 'No se recibió el pago a tiempo', validar: false);
                $cancelados++;
            });

        return $cancelados;
    }

    public function devolverStock(Pedido $pedido): void
    {
        foreach ($pedido->items as $item) {
            Producto::withTrashed()->where('id', $item->producto_id)->increment('stock', $item->cantidad);
        }
    }

    private function registrarEstado(Pedido $pedido, EstadoPedido $estado, ?User $actor, ?string $nota): void
    {
        PedidoEstado::create([
            'pedido_id' => $pedido->id,
            'estado' => $estado->value,
            'nota' => $nota,
            'user_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }

    private function avisarCliente(Pedido $pedido, EstadoPedido $estado, ?string $nota): void
    {
        $cliente = $pedido->cliente ?? User::find($pedido->user_id);
        if (! $cliente) {
            return;
        }

        [$titulo, $mensaje] = match ($estado) {
            EstadoPedido::PagoEnRevision => ['Recibimos tu comprobante', 'Estamos verificando tu pago.'],
            EstadoPedido::Preparando => ['Pago verificado', 'Estamos preparando tu pedido.'],
            EstadoPedido::ListoParaEnvio => ['Tu pedido está listo', 'Pronto saldrá a entrega.'],
            EstadoPedido::EnCamino => ['Tu pedido va en camino', "Código de entrega: {$pedido->codigo_entrega}. Díctalo al repartidor."],
            EstadoPedido::Entregado => ['Pedido entregado', '¡Gracias por tu compra!'],
            EstadoPedido::Cancelado => ['Pedido cancelado', $nota ?: 'Tu pedido fue cancelado.'],
            EstadoPedido::EntregaFallida => ['No pudimos entregar tu pedido', $nota ?: 'Nos pondremos en contacto contigo.'],
            EstadoPedido::PendientePago => ['Pago rechazado', $nota ?: 'Revisa tu comprobante e inténtalo de nuevo.'],
            default => [null, null],
        };

        if ($titulo) {
            $this->notificador->aUsuario($cliente, $titulo, $mensaje, $pedido);
        }
    }
}
