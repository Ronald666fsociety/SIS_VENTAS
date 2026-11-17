<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $t) {
            $t->id();
            $t->string('codigo', 20)->unique(); // PED-2026-000123
            $t->foreignId('user_id')->constrained('users');
            $t->foreignId('direccion_id')->nullable()->constrained('direcciones')->nullOnDelete();
            // Copia de la dirección al momento del pedido
            $t->text('direccion_texto');
            $t->text('referencia')->nullable();
            $t->decimal('lat', 9, 6);
            $t->decimal('lng', 9, 6);
            $t->foreignId('zona_id')->nullable()->constrained('zonas_envio');
            $t->foreignId('repartidor_id')->nullable()->constrained('users');
            $t->decimal('subtotal', 12, 2);
            $t->decimal('costo_envio', 10, 2)->default(0);
            $t->decimal('descuento', 10, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->enum('estado', [
                'pendiente_pago', 'pago_en_revision', 'pago_verificado', 'preparando',
                'listo_para_envio', 'en_camino', 'entregado', 'cancelado', 'entrega_fallida',
            ])->default('pendiente_pago');
            $t->text('notas_cliente')->nullable();
            $t->char('codigo_entrega', 4)->nullable();
            $t->timestampTz('entregado_en')->nullable();
            $t->text('foto_entrega_url')->nullable(); // ruta en disco PRIVADO
            $t->timestampsTz();

            $t->index(['user_id', 'created_at'], 'idx_pedidos_user');
            $t->index('estado', 'idx_pedidos_estado');
            $t->index('repartidor_id', 'idx_pedidos_repart');
        });

        Schema::create('pedido_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->foreignId('producto_id')->constrained('productos');
            $t->string('nombre_producto', 150); // copia histórica
            $t->decimal('precio_unitario', 10, 2); // copia histórica
            $t->integer('cantidad');
            $t->decimal('subtotal', 12, 2);
            $t->index('pedido_id', 'idx_items_pedido');
        });

        Schema::create('pedido_estados', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $t->string('estado', 30);
            $t->text('nota')->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users');
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['pedido_id', 'created_at'], 'idx_pedido_estados');
        });

        Schema::create('pagos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pedido_id')->unique('uq_pagos_pedido')->constrained('pedidos')->cascadeOnDelete();
            $t->string('metodo', 20)->default('qr'); // qr | transferencia | efectivo
            $t->decimal('monto', 12, 2);
            $t->enum('estado', ['pendiente', 'en_revision', 'verificado', 'rechazado'])->default('pendiente');
            $t->text('comprobante_path')->nullable(); // ruta en disco PRIVADO
            $t->string('nro_transaccion', 60)->nullable();
            $t->smallInteger('intentos')->default(0);
            $t->text('motivo_rechazo')->nullable();
            $t->foreignId('verificado_por')->nullable()->constrained('users');
            $t->timestampTz('verificado_en')->nullable();
            $t->timestampsTz();
        });

        Schema::create('configuracion_pago', function (Blueprint $t) {
            $t->smallInteger('id')->primary()->default(1);
            $t->string('banco', 80)->nullable();
            $t->string('titular', 120)->nullable();
            $t->string('nro_cuenta', 40)->nullable();
            $t->text('qr_imagen_url');
            $t->text('instrucciones')->nullable();
            $t->timestampTz('updated_at')->useCurrent();
        });

        Schema::create('notificaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('titulo', 120);
            $t->text('mensaje');
            $t->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $t->boolean('leida')->default(false);
            $t->timestampTz('created_at')->useCurrent();
            $t->index(['user_id', 'leida', 'created_at'], 'idx_notif_user');
        });

        Schema::create('configuracion', function (Blueprint $t) { // parámetros globales
            $t->string('clave', 60)->primary();
            $t->string('valor', 255);
        });
    }

    public function down(): void
    {
        foreach (['configuracion', 'notificaciones', 'configuracion_pago', 'pagos', 'pedido_estados', 'pedido_items', 'pedidos'] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
