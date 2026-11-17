<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nota de Venta {{ $pedido->codigo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; margin: 20px; }
        .header { border-bottom: 2px solid #1976d2; padding-bottom: 10px; margin-bottom: 16px; }
        .title { font-size: 20px; font-weight: bold; color: #1976d2; margin: 0; }
        .sub { color: #555; font-size: 11px; margin-top: 4px; }
        .grid { width: 100%; margin-bottom: 14px; }
        .grid td { vertical-align: top; padding: 3px 0; }
        .box { background: #f7f9fc; border: 1px solid #dce6f2; padding: 10px; border-radius: 4px; margin-bottom: 14px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.items th, table.items td { padding: 7px 8px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        table.items th { background: #eef3f9; color: #333; font-size: 11px; text-transform: uppercase; }
        .num { text-align: right; }
        .totals { width: 45%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 4px 6px; }
        .totals .grand { font-size: 15px; font-weight: bold; color: #1976d2; border-top: 2px solid #1976d2; padding-top: 6px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 10px; font-weight: bold; background: #e8f5e9; color: #2e7d32; }
        .footer { margin-top: 30px; font-size: 10px; color: #777; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Nota de Venta / Comprobante</div>
        <div class="sub">
            {{ $tienda ?? 'Tienda Móvil' }} · Pedido <strong>#{{ $pedido->codigo }}</strong> ·
            Fecha: {{ $pedido->created_at?->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="box">
        <table class="grid">
            <tr>
                <td style="width: 55%;">
                    <strong>Cliente:</strong> {{ $pedido->cliente?->nombre }} {{ $pedido->cliente?->apellido }}<br>
                    <strong>Teléfono:</strong> {{ $pedido->cliente?->telefono }}<br>
                    <strong>Dirección:</strong> {{ $pedido->direccion_texto }}
                    @if($pedido->referencia)
                        <br><strong>Referencia:</strong> {{ $pedido->referencia }}
                    @endif
                </td>
                <td style="width: 45%;">
                    <strong>Estado:</strong> <span class="badge">{{ $pedido->estado->etiqueta() }}</span><br>
                    <strong>Zona:</strong> {{ $pedido->zona?->nombre ?? 'Estándar' }}<br>
                    <strong>Método de pago:</strong> {{ strtoupper($pedido->pago?->metodo ?? 'QR') }}<br>
                    <strong>Estado de pago:</strong> {{ ucfirst(str_replace('_', ' ', $pedido->pago?->estado?->value ?? 'pendiente')) }}
                    @if($pedido->pago?->nro_transaccion)
                        <br><strong>Nro. Transacción:</strong> {{ $pedido->pago->nro_transaccion }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Cant.</th>
                <th class="num">Precio Unit.</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pedido->items as $it)
                <tr>
                    <td>{{ $it->nombre_producto }}</td>
                    <td class="num">{{ $it->cantidad }}</td>
                    <td class="num">Bs {{ number_format((float) $it->precio_unitario, 2) }}</td>
                    <td class="num">Bs {{ number_format((float) $it->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Subtotal:</td>
            <td class="num">Bs {{ number_format((float) $pedido->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Envío:</td>
            <td class="num">Bs {{ number_format((float) $pedido->costo_envio, 2) }}</td>
        </tr>
        @if ((float) $pedido->descuento > 0)
            <tr>
                <td>Descuento:</td>
                <td class="num">- Bs {{ number_format((float) $pedido->descuento, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="grand">TOTAL:</td>
            <td class="num grand">Bs {{ number_format((float) $pedido->total, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        Documento generado electrónicamente el {{ now()->format('d/m/Y H:i') }} · ¡Gracias por tu compra!
    </div>
</body>
</html>
