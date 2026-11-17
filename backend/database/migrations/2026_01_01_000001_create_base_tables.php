<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 100);
            $t->string('apellido', 100)->nullable();
            $t->string('email', 150)->nullable()->unique();
            $t->string('telefono', 20)->unique();
            $t->string('password');
            $t->enum('rol', ['cliente', 'repartidor', 'operador', 'admin'])->default('cliente');
            $t->boolean('activo')->default(true);
            $t->timestampTz('email_verified_at')->nullable();
            $t->timestampTz('telefono_verified_at')->nullable();
            $t->text('avatar_url')->nullable();
            $t->timestampsTz();
            $t->softDeletesTz(); // eliminación de cuenta (exigida por Apple)
        });

        Schema::create('password_reset_tokens', function (Blueprint $t) {
            $t->string('telefono', 20)->primary();
            $t->string('token');
            $t->timestampTz('created_at')->nullable();
        });

        Schema::create('dispositivos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('token')->unique();
            $t->string('plataforma', 10); // android | ios
            $t->string('app', 10)->default('cliente'); // cliente | negocio
            $t->string('version_app', 20)->nullable();
            $t->timestampTz('ultimo_uso')->useCurrent();
        });

        // Versión mínima permitida por app (bloquea versiones viejas)
        Schema::create('app_versiones', function (Blueprint $t) {
            $t->string('app', 10)->primary(); // cliente | negocio
            $t->string('version_minima', 20);
            $t->text('url_tienda')->nullable();
        });

        // Bitácora de acciones sensibles del personal
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->string('accion', 60);
            $t->string('entidad', 40)->nullable();
            $t->unsignedBigInteger('entidad_id')->nullable();
            $t->jsonb('detalle')->nullable();
            $t->ipAddress('ip')->nullable();
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['user_id', 'created_at'], 'idx_auditoria_user');
            $t->index(['entidad', 'entidad_id'], 'idx_auditoria_entidad');
        });

        Schema::create('zonas_envio', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 100);
            $t->decimal('costo_envio', 10, 2)->default(0);
            $t->decimal('pedido_minimo', 10, 2)->default(0);
            $t->jsonb('poligono')->nullable(); // GeoJSON (opcional; la App Negocio usa centro + radio)
            $t->decimal('centro_lat', 9, 6)->nullable();
            $t->decimal('centro_lng', 9, 6)->nullable();
            $t->decimal('radio_km', 5, 2)->nullable();
            $t->boolean('activa')->default(true);
        });

        Schema::create('direcciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('alias', 50);
            $t->text('direccion');
            $t->text('referencia')->nullable();
            $t->decimal('lat', 9, 6);
            $t->decimal('lng', 9, 6);
            $t->foreignId('zona_id')->nullable()->constrained('zonas_envio');
            $t->boolean('es_principal')->default(false);
            $t->timestampTz('created_at')->useCurrent();
            $t->index('user_id', 'idx_direcciones_user');
        });

        Schema::create('categorias', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 100);
            $t->string('slug', 120)->unique();
            $t->text('imagen_url')->nullable();
            $t->integer('orden')->default(0);
            $t->boolean('activa')->default(true);
        });

        Schema::create('productos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('categoria_id')->constrained('categorias');
            $t->string('nombre', 150);
            $t->text('descripcion')->nullable();
            $t->string('sku', 50)->nullable()->unique();
            $t->decimal('precio', 10, 2);
            $t->decimal('precio_oferta', 10, 2)->nullable();
            $t->string('unidad', 20)->default('unidad');
            $t->integer('stock')->default(0);
            $t->integer('stock_minimo')->default(0);
            $t->boolean('activo')->default(true);
            $t->boolean('destacado')->default(false);
            $t->timestampsTz();
            $t->softDeletesTz();
        });

        Schema::create('producto_imagenes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $t->text('url');
            $t->integer('orden')->default(0);
        });
    }

    public function down(): void
    {
        foreach (['producto_imagenes', 'productos', 'categorias', 'direcciones', 'zonas_envio', 'auditoria', 'app_versiones', 'dispositivos', 'password_reset_tokens', 'users'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
