# Sistema de Ventas con QR Estático (100% Móvil)

Arquitectura completa: **Backend API Laravel 12 + App Móvil (Quasar Vue 3 + Capacitor)** con estrategia **un solo código, dos aplicaciones compilables**:
1. **App Cliente** (pública): catálogo, carrito, direcciones con GPS/Leaflet, pedidos, pago QR estático y seguimiento.
2. **App Negocio** (privada para Admin, Operador y Repartidor): verificación de comprobantes a pantalla completa, tablero operativo, pedidos, catálogo, stock rápido, rutas, entregas con código de 4 dígitos y reportes exportables.

---

## 📁 Estructura del Proyecto

```
apk2/
├── backend/                  # API REST Laravel 12 + Sanctum + PostgreSQL/SQLite
│   ├── app/
│   │   ├── Enums/            # Rol, EstadoPedido, EstadoPago
│   │   ├── Models/           # User, Pedido, Pago, Producto, ZonaEnvio, Auditoria, AppVersion...
│   │   ├── Services/         # PedidoService, PagoService, ZonaService, NotificadorService, ImagenService, ReporteService...
│   │   ├── Http/Controllers/Api/V1/ # Auth, Catálogo, Pedidos, Pagos, Repartidor, Admin/*
│   │   ├── Policies/         # PedidoPolicy, DireccionPolicy (control estricto por dueño y rol)
│   │   └── Console/Commands/ # make:admin, pedidos:cancelar-vencidos
│   ├── config/tienda.php     # Tiempos de verificación, plazos de pago, expiración de tokens por app
│   └── tests/Feature/        # 24 tests automatizados (flujo de pedido, transacciones, stock, roles, auditoría)
│
└── app/                      # Proyecto Quasar Framework + Capacitor (Vue 3 + TypeScript)
    ├── capacitor.config.ts   # Configuración de appId dinámico según APP_TARGET
    ├── quasar.config.ts      # Lee APP_TARGET (cliente | negocio)
    ├── package.json          # Scripts: dev:cliente, dev:negocio, build:cliente, build:negocio
    └── src/
        ├── shared/           # API axios interceptors, tipos TS, stores Pinia (auth, carrito, catálogo), MapaPicker
        ├── cliente/          # Vistas de cliente: Inicio, Carrito, Checkout, PagoQR, Seguimiento, MisPedidos, Perfil
        └── negocio/          # Vistas de negocio: Dashboard, Bandeja de Pagos, Pedidos, Catálogo, Repartidor, Más
```

---

## 🚀 Puesta en Marcha Rápida

### 1. Iniciar Backend (API)

```bash
cd backend

# Generar clave y base de datos (por defecto SQLite lista con migraciones y seeders de prueba)
php artisan key:generate
php artisan migrate --seed

# Iniciar servidor de desarrollo
php artisan serve --port=8000
```

#### Usuarios de prueba generados por el Seeder:
- **Admin**: Teléfono `70000001` · Contraseña `password123`
- **Operador**: Teléfono `70000002` · Contraseña `password123`
- **Repartidor**: Teléfono `70000003` · Contraseña `password123`
- **Cliente**: Teléfono `70000004` · Contraseña `password123`

#### Crear un nuevo Administrador desde consola:
```bash
php artisan make:admin --nombre="Dueño" --telefono="79999999" --password="password123"
```

#### Tarea programada de cancelación de pedidos vencidos:
```bash
php artisan pedidos:cancelar-vencidos
```

#### Ejecutar la suite de pruebas automatizadas:
```bash
php artisan test --compact
```

---

### 2. Iniciar Apps Móviles (Frontend Quasar)

```bash
cd app

# Instalar dependencias si no se ha hecho
npm install

# Modo desarrollo - App Cliente
npm run dev:cliente

# Modo desarrollo - App Negocio (Admin / Operador / Repartidor)
npm run dev:negocio
```

#### Compilaciones de producción:
```bash
# Compilar App Cliente
npm run build:cliente

# Compilar App Negocio
npm run build:negocio
```

---

## 🔒 Reglas de Seguridad y Flujo Operativo Implementadas

1. **Tokens Sanctum con caducidad por rol**:
   - `App Cliente`: 30 días de persistencia.
   - `App Negocio`: 24 horas (expiración corta por seguridad en caso de extravío de celular).
2. **Comprobantes y Fotos en Disco Privado**:
   - Nunca son públicos en internet. Se sirven exclusivamente mediante URLs firmadas temporales con expiración (`signed` middleware).
3. **Re-codificación con GD**:
   - Toda imagen subida se procesa, recodifica y comprime (eliminando metadatos o scripts maliciosos embebidos).
4. **Control de Duplicados e Intentos**:
   - `uq_pagos_nro_tx`: índice parcial que impide reutilizar un número de transacción ya verificado.
   - Máximo de 3 intentos de comprobante antes de cancelar el pedido definitivamente y devolver stock.
5. **Stock en Transacción (`lockForUpdate`)**:
   - Descuento inmediato de stock al pedir, restitución atómica al cancelar o rechazar.
6. **Código de entrega de 4 dígitos**:
   - Generado al crear el pedido, revelado al cliente únicamente cuando el pedido está `en_camino`, y exigido al repartidor junto con fotografía en la entrega.
