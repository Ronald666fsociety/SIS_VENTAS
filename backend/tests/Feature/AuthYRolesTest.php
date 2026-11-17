<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\AppVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreaDatos;
use Tests\TestCase;

class AuthYRolesTest extends TestCase
{
    use CreaDatos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararDiscos();
    }

    public function test_registro_crea_siempre_un_cliente(): void
    {
        $this->postJson('/api/v1/auth/registro', [
            'nombre' => 'Ana', 'telefono' => '71234567', 'password' => 'secreto123', 'rol' => 'admin',
        ])->assertCreated()->assertJsonPath('usuario.rol', 'cliente')->assertJsonStructure(['token', 'expira_en']);

        $this->assertSame(Rol::Cliente, User::where('telefono', '71234567')->first()->rol);
    }

    public function test_login_y_separacion_de_apps(): void
    {
        $cliente = $this->usuario();
        $admin = $this->usuario(Rol::Admin);

        $this->postJson('/api/v1/auth/login', ['telefono' => $cliente->telefono, 'password' => 'password123'])
            ->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/v1/auth/login', ['telefono' => $cliente->telefono, 'password' => 'mala'])->assertStatus(422);

        // Un cliente no entra a la App Negocio; el admin sí puede usar la App Cliente
        $this->postJson('/api/v1/auth/login', ['telefono' => $cliente->telefono, 'password' => 'password123', 'app' => 'negocio'])->assertStatus(422);
        $this->postJson('/api/v1/auth/login', ['telefono' => $admin->telefono, 'password' => 'password123'])->assertOk();
        $this->postJson('/api/v1/auth/login', ['telefono' => $admin->telefono, 'password' => 'password123', 'app' => 'negocio'])->assertOk();
    }

    public function test_token_de_negocio_expira_antes_que_el_de_cliente(): void
    {
        $admin = $this->usuario(Rol::Admin);
        $resp = $this->postJson('/api/v1/auth/login', ['telefono' => $admin->telefono, 'password' => 'password123', 'app' => 'negocio']);
        $this->assertTrue(now()->addMinutes(config('tienda.token_minutos.negocio') + 1)->greaterThan($resp->json('expira_en')));
        $this->assertTrue(now()->addDays(2)->lessThan(now()->addMinutes(config('tienda.token_minutos.cliente'))));
    }

    public function test_roles_se_validan_en_el_servidor(): void
    {
        $this->como($this->usuario());
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
        $this->getJson('/api/v1/repartidor/pedidos')->assertForbidden();

        $this->como($this->usuario(Rol::Operador));
        $this->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/productos')->assertForbidden();   // solo admin
        $this->getJson('/api/v1/admin/usuarios')->assertForbidden();
        $this->getJson('/api/v1/admin/reportes/ventas?desde=2026-01-01&hasta=2026-01-31')->assertForbidden();

        $this->como($this->usuario(Rol::Admin));
        $this->getJson('/api/v1/admin/productos')->assertOk();
    }

    public function test_sin_token_no_hay_acceso(): void
    {
        $this->getJson('/api/v1/perfil')->assertUnauthorized();
        $this->getJson('/api/v1/admin/dashboard')->assertUnauthorized();
    }

    public function test_un_token_de_cliente_no_sirve_en_rutas_de_negocio_aunque_el_rol_coincida(): void
    {
        $admin = $this->usuario(Rol::Admin);
        \Laravel\Sanctum\Sanctum::actingAs($admin, ['cliente']);
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_cuenta_desactivada_pierde_acceso_y_sesiones(): void
    {
        $admin = $this->como($this->usuario(Rol::Admin));
        $operador = $this->usuario(Rol::Operador);
        $operador->createToken('negocio:tel', ['negocio']);
        $operador->createToken('negocio:tablet', ['negocio']);

        $this->getJson("/api/v1/admin/usuarios/{$operador->id}/sesiones")->assertOk()->assertJsonCount(2, 'data');
        $this->deleteJson("/api/v1/admin/usuarios/{$operador->id}/sesiones")->assertOk();
        $this->assertSame(0, $operador->tokens()->count());
        $this->assertDatabaseHas('auditoria', ['accion' => 'usuario.sesiones_revocadas', 'user_id' => $admin->id]);

        $this->putJson("/api/v1/admin/usuarios/{$admin->id}", ['activo' => false])->assertStatus(422); // no puede desactivarse a sí mismo
        $this->putJson("/api/v1/admin/usuarios/{$operador->id}", ['activo' => false])->assertOk();

        $this->postJson('/api/v1/auth/login', ['telefono' => $operador->telefono, 'password' => 'password123', 'app' => 'negocio'])->assertStatus(422);
    }

    public function test_version_minima_bloquea_versiones_viejas(): void
    {
        AppVersion::create(['app' => 'cliente', 'version_minima' => '1.2.0', 'url_tienda' => 'https://play.google.com/x']);

        $this->getJson('/api/v1/app/version?app=cliente&version=1.1.9')->assertOk()->assertJsonPath('requiere_actualizacion', true);
        $this->getJson('/api/v1/app/version?app=cliente&version=1.2.0')->assertOk()->assertJsonPath('requiere_actualizacion', false);

        $this->como($this->usuario());
        $this->getJson('/api/v1/perfil', ['X-App' => 'cliente', 'X-App-Version' => '1.0.0'])
            ->assertStatus(426)->assertJsonPath('actualizar', true);
        $this->getJson('/api/v1/perfil', ['X-App' => 'cliente', 'X-App-Version' => '1.3.0'])->assertOk();
    }

    public function test_registro_de_dispositivo_deduce_la_app_por_el_rol(): void
    {
        $this->como($this->usuario(Rol::Operador));
        $this->postJson('/api/v1/dispositivos', ['token' => 'fcm-abc', 'plataforma' => 'android', 'version_app' => '1.0.0'])->assertCreated();
        $this->assertDatabaseHas('dispositivos', ['token' => 'fcm-abc', 'app' => 'negocio', 'version_app' => '1.0.0']);
    }

    public function test_make_admin_crea_el_primer_admin(): void
    {
        $this->artisan('make:admin', ['--nombre' => 'Dueño', '--telefono' => '79999999', '--password' => 'claveSegura1'])
            ->assertSuccessful();

        $this->assertSame(Rol::Admin, User::where('telefono', '79999999')->first()->rol);
        $this->artisan('make:admin', ['--nombre' => 'X', '--telefono' => '79999999', '--password' => 'claveSegura1'])->assertFailed();
    }

    public function test_eliminar_cuenta_anonimiza_y_bloquea_si_hay_pedidos_en_curso(): void
    {
        $this->zonaCentro();
        $p = $this->producto(10, 50);
        $cliente = $this->como($this->usuario());
        $id = $this->postJson('/api/v1/pedidos', $this->cuerpoPedido($p, 3))->json('data.id');

        $this->deleteJson('/api/v1/perfil', ['password' => 'password123'])->assertStatus(422);
        $this->postJson("/api/v1/pedidos/{$id}/cancelar")->assertOk();
        $this->deleteJson('/api/v1/perfil', ['password' => 'mala'])->assertStatus(422);
        $this->deleteJson('/api/v1/perfil', ['password' => 'password123'])->assertOk();

        $this->assertNull(User::find($cliente->id));
        $this->assertSame('Cuenta eliminada', User::withTrashed()->find($cliente->id)->nombre);
    }

    public function test_admin_gestiona_catalogo_con_imagenes_y_auditoria(): void
    {
        $admin = $this->como($this->usuario(Rol::Admin));
        $cat = $this->postJson('/api/v1/admin/categorias', ['nombre' => 'Bebidas Frías'])->assertCreated()->json();
        $this->assertSame('bebidas-frias', $cat['slug']);

        $resp = $this->post('/api/v1/admin/productos', [
            'categoria_id' => $cat['id'], 'nombre' => 'Agua', 'precio' => 8, 'stock' => 10,
            'imagenes' => [UploadedFile::fake()->image('a.jpg', 800, 800)],
        ], ['Accept' => 'application/json'])->assertCreated();
        $id = $resp->json('data.id');
        $this->assertCount(1, $resp->json('data.imagenes'));

        $this->putJson("/api/v1/admin/productos/{$id}", ['precio' => 9, 'precio_oferta' => 7])->assertOk();
        $this->patchJson("/api/v1/admin/productos/{$id}/stock", ['delta' => -3])->assertOk()->assertJsonPath('data.stock', 7);
        $this->patchJson("/api/v1/admin/productos/{$id}/stock", ['delta' => -30])->assertStatus(422);

        $this->assertDatabaseHas('auditoria', ['accion' => 'producto.precio_editado', 'user_id' => $admin->id]);
        $this->assertDatabaseHas('auditoria', ['accion' => 'producto.stock_editado', 'entidad_id' => $id]);

        // El cliente ve el catálogo sin datos internos
        $this->como($this->usuario());
        $this->getJson('/api/v1/productos')->assertOk()->assertJsonMissingPath('data.0.stock_minimo')->assertJsonPath('data.0.precio_final', '7.00');
    }

    public function test_reportes_json_pdf_y_excel(): void
    {
        $this->como($this->usuario(Rol::Admin));
        $q = '?desde=2026-01-01&hasta=2026-12-31';

        $this->getJson('/api/v1/admin/reportes/ventas'.$q)->assertOk()->assertJsonPath('resumen.pedidos', 0);
        $this->get('/api/v1/admin/reportes/ventas'.$q.'&formato=pdf', ['Accept' => 'application/json'])
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get('/api/v1/admin/reportes/ventas'.$q.'&formato=xlsx', ['Accept' => 'application/json'])->assertOk();
    }

    public function test_zona_validar_y_direcciones_privadas(): void
    {
        $this->zonaCentro();
        $a = $this->como($this->usuario());

        $this->postJson('/api/v1/zonas/validar', ['lat' => -17.784, 'lng' => -63.183])->assertOk()
            ->assertJsonPath('cubre', true)->assertJsonPath('costo_envio', '8.00');
        $this->postJson('/api/v1/zonas/validar', ['lat' => 0, 'lng' => 0])->assertOk()->assertJsonPath('cubre', false);

        $id = $this->postJson('/api/v1/direcciones', ['alias' => 'Casa', 'direccion' => 'Calle 1', 'lat' => -17.784, 'lng' => -63.183])
            ->assertCreated()->assertJsonPath('es_principal', true)->json('id');

        $this->como($this->usuario());
        $this->getJson("/api/v1/direcciones/{$id}")->assertForbidden();
        $this->deleteJson("/api/v1/direcciones/{$id}")->assertForbidden();
    }
}
