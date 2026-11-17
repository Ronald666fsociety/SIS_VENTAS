<?php

namespace App\Console\Commands;

use App\Services\PedidoService;
use Illuminate\Console\Command;

class CancelarPedidosVencidos extends Command
{
    protected $signature = 'pedidos:cancelar-vencidos';

    protected $description = 'Cancela pedidos sin pago dentro del plazo y devuelve el stock';

    public function handle(PedidoService $pedidos): int
    {
        $n = $pedidos->cancelarVencidos();
        $this->info("Pedidos cancelados por falta de pago: {$n}");

        return self::SUCCESS;
    }
}
