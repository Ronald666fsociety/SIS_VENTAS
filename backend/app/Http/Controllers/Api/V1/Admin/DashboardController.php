<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $hoy = now()->startOfDay();
        $vendidos = array_map(fn ($e) => $e->value, EstadoPedido::vendidos());

        return response()->json([
            'pagos_por_verificar' => Pago::where('estado', EstadoPago::EnRevision->value)->count(),
            'pedidos_hoy' => Pedido::where('created_at', '>=', $hoy)->count(),
            'ventas_hoy' => number_format((float) Pedido::where('created_at', '>=', $hoy)->whereIn('estado', $vendidos)->sum('total'), 2, '.', ''),
            'stock_bajo' => Producto::where('activo', true)->whereColumn('stock', '<=', 'stock_minimo')->count(),
            'por_preparar' => Pedido::where('estado', EstadoPedido::Preparando->value)->count(),
            'listos_para_envio' => Pedido::where('estado', EstadoPedido::ListoParaEnvio->value)->count(),
            'en_camino' => Pedido::where('estado', EstadoPedido::EnCamino->value)->count(),
        ]);
    }
}
