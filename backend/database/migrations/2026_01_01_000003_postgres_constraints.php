<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restricciones e índices parciales. El índice único de nro_transaccion funciona en
 * PostgreSQL y SQLite; el resto es específico de PostgreSQL (se omite en SQLite para tests).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Frena comprobantes duplicados
        DB::statement("CREATE UNIQUE INDEX uq_pagos_nro_tx ON pagos(nro_transaccion) WHERE nro_transaccion IS NOT NULL AND estado <> 'rechazado'");

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE productos ADD CONSTRAINT chk_precio CHECK (precio >= 0)');
        DB::statement('ALTER TABLE productos ADD CONSTRAINT chk_precio_oferta CHECK (precio_oferta IS NULL OR precio_oferta >= 0)');
        DB::statement('ALTER TABLE productos ADD CONSTRAINT chk_stock CHECK (stock >= 0)');
        DB::statement('ALTER TABLE pedido_items ADD CONSTRAINT chk_cantidad CHECK (cantidad > 0)');
        DB::statement('ALTER TABLE configuracion_pago ADD CONSTRAINT chk_una_fila CHECK (id = 1)');

        DB::statement('CREATE INDEX idx_productos_categoria ON productos(categoria_id) WHERE deleted_at IS NULL');
        DB::statement("CREATE INDEX idx_productos_nombre ON productos USING gin (to_tsvector('spanish', nombre))");
        DB::statement('CREATE INDEX idx_pedidos_repart_parcial ON pedidos(repartidor_id) WHERE repartidor_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uq_pagos_nro_tx');
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }
        DB::statement('DROP INDEX IF EXISTS idx_pedidos_repart_parcial');
        DB::statement('DROP INDEX IF EXISTS idx_productos_nombre');
        DB::statement('DROP INDEX IF EXISTS idx_productos_categoria');
    }
};
