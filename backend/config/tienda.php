<?php

return [
    // Minutos que tiene el cliente para pagar antes de cancelar el pedido automáticamente.
    'plazo_pago_minutos' => (int) env('PLAZO_PAGO_MINUTOS', 120),

    // Intentos máximos de comprobante por pedido.
    'max_intentos_pago' => (int) env('MAX_INTENTOS_PAGO', 3),

    // Minutos prometidos al cliente para verificar el pago (solo informativo en la app).
    'verificacion_minutos' => (int) env('VERIFICACION_MINUTOS', 15),

    // Vida de los tokens según la app que inicia sesión (minutos).
    'token_minutos' => [
        'cliente' => (int) env('TOKEN_CLIENTE_MINUTOS', 60 * 24 * 30),
        'negocio' => (int) env('TOKEN_NEGOCIO_MINUTOS', 60 * 24), // corta: App Negocio
    ],

    // Disco privado: comprobantes y fotos de entrega. Disco público: imágenes de productos y QR.
    'disco_privado' => env('DISCO_PRIVADO', 'local'),
    'disco_publico' => env('DISCO_PUBLICO', 'public'),

    // Minutos de vida de la URL firmada del comprobante.
    'url_comprobante_minutos' => (int) env('URL_COMPROBANTE_MINUTOS', 10),

    // Prefijo wa.me para contacto por WhatsApp (código de país sin +).
    'whatsapp_numero' => env('WHATSAPP_NUMERO'),
];
