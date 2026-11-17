<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->decimal('repartidor_lat', 10, 7)->nullable()->after('lng');
            $table->decimal('repartidor_lng', 10, 7)->nullable()->after('repartidor_lat');
            $table->timestamp('repartidor_ubicacion_at')->nullable()->after('repartidor_lng');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['repartidor_lat', 'repartidor_lng', 'repartidor_ubicacion_at']);
        });
    }
};
