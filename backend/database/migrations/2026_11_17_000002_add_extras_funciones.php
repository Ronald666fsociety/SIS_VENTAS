<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_pago', function (Blueprint $table) {
            $table->boolean('aceptar_efectivo')->default(true)->after('instrucciones');
        });

        Schema::table('pedidos', function (Blueprint $table) {
            $table->unsignedTinyInteger('calificacion')->nullable()->after('foto_entrega_url');
            $table->text('comentario_calificacion')->nullable()->after('calificacion');
            $table->timestamp('calificado_at')->nullable()->after('comentario_calificacion');
        });

        Schema::create('favoritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'producto_id'], 'uq_favoritos_user_prod');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favoritos');

        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['calificacion', 'comentario_calificacion', 'calificado_at']);
        });

        Schema::table('configuracion_pago', function (Blueprint $table) {
            $table->dropColumn('aceptar_efectivo');
        });
    }
};
