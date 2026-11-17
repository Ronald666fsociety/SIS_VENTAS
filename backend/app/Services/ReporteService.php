<?php

namespace App\Services;

use App\Enums\EstadoPedido;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    public function ventas(Carbon $desde, Carbon $hasta): array
    {
        $inicio = $desde->copy()->startOfDay();
        $fin = $hasta->copy()->endOfDay();
        $estados = array_map(fn (EstadoPedido $e) => $e->value, EstadoPedido::vendidos());

        $pedidos = DB::table('pedidos')
            ->whereBetween('created_at', [$inicio, $fin])
            ->whereIn('estado', $estados)
            ->get(['id', 'total', 'subtotal', 'costo_envio', 'zona_id', 'created_at']);

        $totalVentas = $pedidos->reduce(fn ($c, $p) => bcadd($c, (string) $p->total, 2), '0.00');
        $totalEnvios = $pedidos->reduce(fn ($c, $p) => bcadd($c, (string) $p->costo_envio, 2), '0.00');
        $cantidad = $pedidos->count();

        $porDia = $pedidos
            ->groupBy(fn ($p) => Carbon::parse($p->created_at)->toDateString())
            ->map(fn ($grupo, $dia) => [
                'fecha' => $dia,
                'pedidos' => $grupo->count(),
                'total' => $grupo->reduce(fn ($c, $p) => bcadd($c, (string) $p->total, 2), '0.00'),
            ])
            ->sortKeys()
            ->values()
            ->all();

        $porProducto = DB::table('pedido_items as i')
            ->join('pedidos as p', 'p.id', '=', 'i.pedido_id')
            ->whereBetween('p.created_at', [$inicio, $fin])
            ->whereIn('p.estado', $estados)
            ->groupBy('i.producto_id', 'i.nombre_producto')
            ->orderByDesc(DB::raw('SUM(i.cantidad)'))
            ->limit(50)
            ->get([
                'i.producto_id',
                'i.nombre_producto as producto',
                DB::raw('SUM(i.cantidad) as unidades'),
                DB::raw('SUM(i.subtotal) as total'),
            ])
            ->map(fn ($r) => [
                'producto_id' => $r->producto_id,
                'producto' => $r->producto,
                'unidades' => (int) $r->unidades,
                'total' => number_format((float) $r->total, 2, '.', ''),
            ])->all();

        $nombresZonas = DB::table('zonas_envio')->pluck('nombre', 'id');
        $porZona = $pedidos
            ->groupBy(fn ($p) => $p->zona_id ?? 0)
            ->map(fn ($grupo, $zonaId) => [
                'zona' => $nombresZonas[$zonaId] ?? 'Sin zona',
                'pedidos' => $grupo->count(),
                'total' => $grupo->reduce(fn ($c, $p) => bcadd($c, (string) $p->total, 2), '0.00'),
            ])
            ->sortByDesc(fn ($z) => (float) $z['total'])
            ->values()
            ->all();

        return [
            'desde' => $inicio->toDateString(),
            'hasta' => $fin->toDateString(),
            'resumen' => [
                'pedidos' => $cantidad,
                'ventas' => $totalVentas,
                'envios' => $totalEnvios,
                'ticket_promedio' => $cantidad ? bcdiv($totalVentas, (string) $cantidad, 2) : '0.00',
            ],
            'por_dia' => $porDia,
            'por_producto' => $porProducto,
            'por_zona' => $porZona,
        ];
    }

    /** Filas planas para Excel. */
    public function filasExcel(array $r): array
    {
        $filas = [
            ['Reporte de ventas', $r['desde'].' a '.$r['hasta']],
            [],
            ['Resumen'],
            ['Pedidos', $r['resumen']['pedidos']],
            ['Ventas (Bs)', $r['resumen']['ventas']],
            ['Envíos (Bs)', $r['resumen']['envios']],
            ['Ticket promedio (Bs)', $r['resumen']['ticket_promedio']],
            [],
            ['Ventas por día'],
            ['Fecha', 'Pedidos', 'Total (Bs)'],
        ];
        foreach ($r['por_dia'] as $d) {
            $filas[] = [$d['fecha'], $d['pedidos'], $d['total']];
        }
        $filas[] = [];
        $filas[] = ['Productos más vendidos'];
        $filas[] = ['Producto', 'Unidades', 'Total (Bs)'];
        foreach ($r['por_producto'] as $p) {
            $filas[] = [$p['producto'], $p['unidades'], $p['total']];
        }
        $filas[] = [];
        $filas[] = ['Ventas por zona'];
        $filas[] = ['Zona', 'Pedidos', 'Total (Bs)'];
        foreach ($r['por_zona'] as $z) {
            $filas[] = [$z['zona'], $z['pedidos'], $z['total']];
        }

        return $filas;
    }
}
