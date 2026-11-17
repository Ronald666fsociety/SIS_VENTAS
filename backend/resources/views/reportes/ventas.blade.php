<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de ventas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        h2 { font-size: 14px; margin-top: 18px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f4f4f4; }
        .num { text-align: right; }
        .resumen td { font-size: 13px; }
    </style>
</head>
<body>
    <h1>Reporte de ventas</h1>
    <div>Del {{ $r['desde'] }} al {{ $r['hasta'] }}</div>

    <h2>Resumen</h2>
    <table class="resumen">
        <tr><td>Pedidos</td><td class="num">{{ $r['resumen']['pedidos'] }}</td></tr>
        <tr><td>Ventas</td><td class="num">Bs {{ number_format((float) $r['resumen']['ventas'], 2) }}</td></tr>
        <tr><td>Envíos</td><td class="num">Bs {{ number_format((float) $r['resumen']['envios'], 2) }}</td></tr>
        <tr><td>Ticket promedio</td><td class="num">Bs {{ number_format((float) $r['resumen']['ticket_promedio'], 2) }}</td></tr>
    </table>

    <h2>Ventas por día</h2>
    <table>
        <tr><th>Fecha</th><th class="num">Pedidos</th><th class="num">Total (Bs)</th></tr>
        @foreach ($r['por_dia'] as $d)
            <tr><td>{{ $d['fecha'] }}</td><td class="num">{{ $d['pedidos'] }}</td><td class="num">{{ number_format((float) $d['total'], 2) }}</td></tr>
        @endforeach
    </table>

    <h2>Productos más vendidos</h2>
    <table>
        <tr><th>Producto</th><th class="num">Unidades</th><th class="num">Total (Bs)</th></tr>
        @foreach ($r['por_producto'] as $p)
            <tr><td>{{ $p['producto'] }}</td><td class="num">{{ $p['unidades'] }}</td><td class="num">{{ number_format((float) $p['total'], 2) }}</td></tr>
        @endforeach
    </table>

    <h2>Ventas por zona</h2>
    <table>
        <tr><th>Zona</th><th class="num">Pedidos</th><th class="num">Total (Bs)</th></tr>
        @foreach ($r['por_zona'] as $z)
            <tr><td>{{ $z['zona'] }}</td><td class="num">{{ $z['pedidos'] }}</td><td class="num">{{ number_format((float) $z['total'], 2) }}</td></tr>
        @endforeach
    </table>
</body>
</html>
