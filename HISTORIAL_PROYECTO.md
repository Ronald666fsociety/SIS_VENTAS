# Historial Completo y Guía Rápida del Proyecto (`SIS_VENTAS`)

> **Propósito de este archivo**: Si se pierde o reinicia la conversación con la IA, comparte o abre este archivo (`HISTORIAL_PROYECTO.md`) para recuperar al instante toda la arquitectura, decisiones, arreglos, credenciales, comandos y estado actual del sistema.

---

## 1. Resumen Ejecutivo

Sistema de ventas móvil completo (**Laravel 12 API + Quasar Vue 3 TypeScript + Capacitor 8 Android**) construido bajo la arquitectura de **un solo código frontend para dos aplicaciones compilables**:

1. **App Cliente (`APP_TARGET=cliente` / `com.tuempresa.tienda` / *Tienda Express*)**:
   - Catálogo con búsqueda, categorías dinámicas, filtros (*Ofertas*, *Destacados*, *Favoritos*), visor de imagen completa (`VisorImagen.vue`), carrito con controles `+` / `-` / `Quitar`, checkout con mapa Leaflet (cobertura en **toda Bolivia**), pago **por QR estático** o **en efectivo contra entrega**, seguimiento con **mapa GPS en tiempo real del repartidor + distancia/ETA**, descarga de **Nota de Venta / Comprobante en PDF**, calificación (`1–5` estrellas + comentario), historial de gastos, notificaciones en vivo y recuperación de contraseña.
2. **App Negocio (`APP_TARGET=negocio` / `com.tuempresa.tienda.negocio` / *Tienda Negocio*)**:
   - Acceso exclusivo por rol (**Admin**, **Operador**, **Repartidor**) protegido con Sanctum (`ability:negocio`): dashboard con métricas en vivo, bandeja de verificación de pagos QR (aprobar/rechazar con máximo 3 reintentos), gestión completa de pedidos y asignación de repartidores, CRUD de catálogo con subida de múltiples fotos y categorías libres, modo repartidor con envío GPS en vivo y entrega con código de 4 dígitos + foto, configuración de pago QR / efectivo, zonas de envío (presets de ciudades de Bolivia), gestión de personal, auditoría y exportación de reportes en PDF / Excel.

---

## 2. Servidores Locales, Puertos y Credenciales Demo

### Comandos para levantar el entorno local (Windows / PowerShell)

1. **Backend API (Laravel 12 + SQLite)** — Puerto **`8000`** (escuchando en `0.0.0.0` para que también sea accesible desde el celular en la misma red Wi-Fi):
   ```powershell
   cd c:\Users\RON\Desktop\apk2\backend
   php artisan serve --host=0.0.0.0 --port=8000
   ```
2. **Frontend App Cliente (Quasar Dev)** — Puerto **`9000`**:
   ```powershell
   cd c:\Users\RON\Desktop\apk2\app
   npm run dev:cliente
   ```
3. **Frontend App Negocio (Quasar Dev)** — Puerto **`9100`**:
   ```powershell
   cd c:\Users\RON\Desktop\apk2\app
   npm run dev:negocio
   ```

### Usuarios Demo del Seeder (`DatabaseSeeder.php`)
La contraseña de todas las cuentas demo es **`password123`**:

| Rol | Teléfono | App donde inicia sesión |
| :--- | :--- | :--- |
| **Admin** | `70000001` | App Negocio (`9100` / `Tienda Negocio`) |
| **Operador** | `70000002` | App Negocio (`9100` / `Tienda Negocio`) |
| **Repartidor** | `70000003` | App Negocio (`9100` / `Tienda Negocio`) |
| **Cliente** | `70000004` | App Cliente (`9000` / `Tienda Express`) |

- **IP LAN detectada en compilación de APKs**: `http://192.168.10.127:8000/api/v1` (el firewall de Windows permite tráfico entrante a `php.exe`). Si la IP cambia al cambiar de Wi-Fi, ambas pantallas de login incluyen el botón **"Configurar servidor (IP)"** (`ServerConfigDialog.vue`) para actualizar y probar la URL sin recompilar los `.apk`.

---

## 3. Cronología Completa de lo Realizado en la Conversación

1. **Diseño de arquitectura móvil unificada**:
   - Se definió un único código frontend en `app/` usando `APP_TARGET` (`cliente` | `negocio`) contra una sola API REST en `backend/` con Laravel Sanctum y separación estricta de permisos (`ability:cliente` vs `ability:negocio`).
2. **Construcción inicial del Backend (`backend/`) y Frontend (`app/`)**:
   - Modelos, enums (`Rol`, `EstadoPedido`, `EstadoPago`), servicios (`PedidoService`, `PagoService`, `ImagenService`, `NotificadorService`, `ReporteService`, `ZonaService`, `AuditoriaService`), políticas (`PedidoPolicy`, `DireccionPolicy`) y suite inicial de pruebas en `tests/Feature/`.
3. **Resolución de pantalla en blanco, login y registro**:
   - Se corrigió la configuración de Vite/Quasar en `app/quasar.config.ts` cambiando `rawDefine` por `define` + `defineEnv` para inyectar correctamente `__APP_TARGET__` y `__API_URL__`.
   - Se reparó el flujo de registro e inicio de sesión para ambas apps y el manejo de errores/tokens en `auth.ts` y `client.ts`.
4. **Flujo completo de comprobantes QR, fotos, carrito y categorías**:
   - Verificación y aprobación/rechazo de comprobantes QR desde App Negocio hasta preparar/entregar.
   - Subida real de múltiples imágenes por producto y categoría libre desde `CatalogoAdminPage.vue`.
   - Botones de agregar, incrementar (`+`), decrementar (`-`) y quitar del carrito tanto en inicio como en `CarritoPage.vue`.
5. **Accesos diferenciados (Admin/Negocio vs Clientes) + Visor de imágenes**:
   - Separación clara de rutas y vistas de login (`LoginPage.vue`, `RegistroPage.vue`, `LoginNegocioPage.vue`).
   - Se agregó el componente `VisorImagen.vue` para abrir fotos completas con zoom al hacer clic en tarjetas o carruseles.
6. **Envíos a toda Bolivia + Rastreo GPS en tiempo real con ETA**:
   - Migración `2026_11_17_000001_add_repartidor_ubicacion_to_pedidos.php`, presets de ciudades y cobertura nacional (`Toda Bolivia (Nacional)`), envío periódico de ubicación del repartidor (`RepartidorPage.vue`) y mapa Leaflet en vivo con cálculo de distancia y tiempo estimado (`SeguimientoPage.vue`).
7. **Cinco funcionalidades adicionales implementadas**:
   - **Campanita de notificaciones en vivo**: [`NotificacionesBell.vue`](file:///c:/Users/RON/Desktop/apk2/app/src/shared/components/NotificacionesBell.vue) en `ClienteLayout.vue` y `NegocioLayout.vue` con polling cada 15s y marcado individual/masivo.
   - **Comprobante / Nota de Venta PDF para el cliente**: [`comprobante.blade.php`](file:///c:/Users/RON/Desktop/apk2/backend/resources/views/pedidos/comprobante.blade.php) y endpoint `GET /api/v1/pedidos/{pedido}/comprobante-pdf` usando `barryvdh/laravel-dompdf`.
   - **Pago en efectivo contra entrega (opcional)**: Campo `aceptar_efectivo` en `configuracion_pago`, selector en `CheckoutPage.vue`, flujo directo a `Preparando` y verificación automática del pago al entregar.
   - **Recuperar contraseña en Login**: [`RecuperarPasswordDialog.vue`](file:///c:/Users/RON/Desktop/apk2/app/src/shared/components/RecuperarPasswordDialog.vue) en 2 pasos (`/auth/recuperar` y `/auth/restablecer`, con `codigo_dev` fuera de producción).
   - **Calificación, Favoritos e Historial de Gastos**: Estrellas `1–5` tras entrega (`POST /api/v1/pedidos/{pedido}/calificar`), tabla pivote `favoritos` + store Pinia [`favoritos.ts`](file:///c:/Users/RON/Desktop/apk2/app/src/shared/stores/favoritos.ts) y resumen de gastos (`GET /api/v1/perfil/gastos`).
   - **Tests agregados**: `test_recuperar_y_restablecer_contrasena` y `test_efectivo_comprobante_pdf_calificacion_favoritos_y_gastos` en `FlujoPedidoTest.php` (total: **28 tests pasados, 200 assertions**).
8. **Renovación profesional de tipografía y diseño UI**:
   - Carga de **Plus Jakarta Sans** + **Inter** en [`index.html`](file:///c:/Users/RON/Desktop/apk2/app/index.html) y sistema de diseño unificado en [`app.css`](file:///c:/Users/RON/Desktop/apk2/app/src/css/app.css) (`tabular-nums`, bordes redondeados, tarjetas interactivas, headers translúcidos `glass`).
   - Rediseño de pantallas de login/registro, layouts y módulos de Cliente y Negocio.
9. **Empaquetado móvil Android (`.apk`)**:
   - Instalación de **Eclipse Temurin JDK 21**, **Android CLI (`android.exe`)**, **Android SDK 36** (`platforms/android-36`, `build-tools/36.0.0`, `platform-tools`) y **`@capacitor/android`**.
   - Soporte nativo de descarga/compartición de PDFs y reportes mediante [`descargar.ts`](file:///c:/Users/RON/Desktop/apk2/app/src/shared/utils/descargar.ts) (`@capacitor/filesystem` + `@capacitor/share`), tráfico HTTP local habilitado (`usesCleartextTraffic="true"` + `androidScheme: 'http'`), permisos de cámara/GPS/almacenamiento en [`AndroidManifest.xml`](file:///c:/Users/RON/Desktop/apk2/app/android/app/src/main/AndroidManifest.xml) y soporte multi-target Gradle (`-PappTarget=cliente|negocio`).
   - APKs generados en [`apks/`](file:///c:/Users/RON/Desktop/apk2/apks):
     - [`TiendaExpress-Cliente.apk`](file:///c:/Users/RON/Desktop/apk2/apks/TiendaExpress-Cliente.apk) (`9,586,207` bytes · `com.tuempresa.tienda`)
     - [`TiendaNegocio-Staff.apk`](file:///c:/Users/RON/Desktop/apk2/apks/TiendaNegocio-Staff.apk) (`9,517,313` bytes · `com.tuempresa.tienda.negocio`)
10. **Publicación en GitHub**:
    - Inicialización de Git en la raíz `c:\Users\RON\Desktop\apk2`, creación de `.gitignore` y push completo a **`https://github.com/Ronald666fsociety/SIS_VENTAS.git`** en la rama **`main`** (commit `ebec2e89c79e2ac0f2d0f292c668df6bff76b9a0`, 253 archivos).

---

## 4. Estructura y Archivos Clave

```text
apk2/
├── .gitignore
├── README.md
├── HISTORIAL_PROYECTO.md          # Este documento de contexto rápido
├── apks/
│   ├── TiendaExpress-Cliente.apk  # APK Android para clientes
│   └── TiendaNegocio-Staff.apk    # APK Android para Admin / Operador / Repartidor
├── backend/                       # Laravel 12 + Sanctum + SQLite + DomPDF
│   ├── routes/api.php             # Rutas /api/v1 (públicas, cliente, admin, repartidor)
│   ├── app/
│   │   ├── Http/Controllers/Api/V1/
│   │   ├── Http/Resources/        # PedidoResource, PagoResource, ProductoResource, UserResource
│   │   ├── Models/                # User, Pedido, Pago, Producto, ConfiguracionPago, ZonaEnvio, etc.
│   │   └── Services/              # Lógica de negocio (pedidos, pagos, GPS, reportes, notificaciones)
│   ├── database/
│   │   ├── migrations/            # 8 archivos de migración
│   │   └── seeders/DatabaseSeeder.php
│   ├── resources/views/
│   │   ├── pedidos/comprobante.blade.php
│   │   └── reportes/ventas.blade.php
│   └── tests/Feature/             # AuthYRolesTest.php, FlujoPedidoTest.php
└── app/                           # Quasar 2 (Vue 3 + TS) + Capacitor 8
    ├── capacitor.config.ts        # Configuración dinámica por APP_TARGET (http + cleartext)
    ├── quasar.config.ts           # Lee APP_TARGET y API_URL
    ├── index.html                 # Carga Google Fonts (Plus Jakarta Sans + Inter)
    ├── android/                   # Proyecto nativo Gradle + AndroidManifest + build.gradle multi-target
    └── src/
        ├── css/app.css            # Tokens, tipografía, cards glass y utilidades visuales
        ├── shared/
        │   ├── api/client.ts      # Axios + custom_api_url override + Bearer Sanctum
        │   ├── components/        # NotificacionesBell, RecuperarPasswordDialog, ServerConfigDialog, VisorImagen, MapaPicker, EstadoBadge, PrecioTag
        │   ├── stores/            # auth.ts, carrito.ts, catalogo.ts, favoritos.ts
        │   ├── types/index.ts     # Tipos TS compartidos
        │   └── utils/             # bolivia.ts, descargar.ts
        ├── cliente/               # ClienteLayout + Inicio, ProductoDetalle, Carrito, Checkout, PagoQR, MisPedidos, Seguimiento, Perfil, Login, Registro
        └── negocio/               # NegocioLayout + Dashboard, Pagos, PedidosAdmin, CatalogoAdmin, Repartidor, Mas, LoginNegocio
```

---

## 5. Base de Datos y Migraciones (`backend/database/`)

Motor actual de desarrollo: **SQLite** (`backend/database/database.sqlite`, excluido del repo Git vía `.gitignore`).

Migraciones registradas:
1. `0001_01_01_000001_create_cache_table.php` y `0001_01_01_000002_create_jobs_table.php`
2. `2026_01_01_000001_create_base_tables.php` (`users`, `categorias`, `productos`, `producto_imagenes`, `zonas_envio`, `direcciones`, `configuracion_pago`, `app_versions`, `password_reset_tokens`)
3. `2026_01_01_000002_create_pedidos_pagos_tables.php` (`pedidos`, `pedido_items`, `pedido_estados`, `pagos`, `notificaciones`, `dispositivos`, `auditoria`)
4. `2026_01_01_000003_postgres_constraints.php`
5. `2026_11_16_162431_create_personal_access_tokens_table.php` (Sanctum)
6. `2026_11_17_000001_add_repartidor_ubicacion_to_pedidos.php` (tracking GPS en tiempo real)
7. `2026_11_17_000002_add_extras_funciones.php` (`aceptar_efectivo`, `calificacion`, `comentario_calificacion`, `calificado_at`, tabla `favoritos`)

Si clonas desde cero en otra máquina:
```powershell
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
```

---

## 6. Notas Técnicas Clave y Recompilación de APKs

### Advertencia importante en Windows durante desarrollo
- **No editar `app/quasar.config.ts` mientras `quasar dev` está corriendo**, porque en Windows puede congelarse en `"Applying quasar.config changes..."`. Reiniciar los dev servers si hace falta tocar ese archivo.
- **Strict TypeScript**: El proyecto usa `strict: true` en TypeScript; verificar siempre con `npm run typecheck` (`vue-tsc --noEmit`).

### Herramientas instaladas en esta PC para compilar `.apk`
- **JDK 21**: `C:\Program Files\Eclipse Adoptium\jdk-21.0.13.9-hotspot`
- **Android CLI**: `C:\Users\RON\AppData\AndroidCLI\android.exe`
- **Android SDK**: `C:\Users\RON\AppData\Local\Android\Sdk` (`platforms/android-36`, `build-tools/36.0.0`, `platform-tools`)

### Comandos para regenerar ambos `.apk` (ej. si cambias código o la IP por defecto)
```powershell
$env:JAVA_HOME='C:\Program Files\Eclipse Adoptium\jdk-21.0.13.9-hotspot'
$env:ANDROID_HOME='C:\Users\RON\AppData\Local\Android\Sdk'
$env:PATH="$env:JAVA_HOME\bin;$env:PATH"
cd c:\Users\RON\Desktop\apk2\app

# 1) APK Cliente
npx cross-env APP_TARGET=cliente API_URL=http://192.168.10.127:8000/api/v1 quasar build
npx cross-env APP_TARGET=cliente cap sync android
cd c:\Users\RON\Desktop\apk2\app\android
.\gradlew.bat assembleDebug -PappTarget=cliente --no-daemon
Copy-Item -Force app\build\outputs\apk\debug\app-debug.apk c:\Users\RON\Desktop\apk2\apks\TiendaExpress-Cliente.apk

# 2) APK Negocio
cd c:\Users\RON\Desktop\apk2\app
npx cross-env APP_TARGET=negocio API_URL=http://192.168.10.127:8000/api/v1 quasar build
npx cross-env APP_TARGET=negocio cap sync android
cd c:\Users\RON\Desktop\apk2\app\android
.\gradlew.bat assembleDebug -PappTarget=negocio --no-daemon
Copy-Item -Force app\build\outputs\apk\debug\app-debug.apk c:\Users\RON\Desktop\apk2\apks\TiendaNegocio-Staff.apk
```

---

## 7. Estado Verificado al Cierre

- **Backend**: `php artisan test` -> **28 passed (200 assertions)** en `c:\Users\RON\Desktop\apk2\backend`.
- **Frontend**: `npm run typecheck`, `npm run build:cliente` y `npm run build:negocio` -> código `0` sin errores.
- **Servidores activos locales**:
  - API Laravel: `http://127.0.0.1:8000` y `http://192.168.10.127:8000`
  - App Cliente: `http://localhost:9000`
  - App Negocio: `http://localhost:9100`
- **Git / GitHub**: Subido a `https://github.com/Ronald666fsociety/SIS_VENTAS.git` (`main`, commit inicial `ebec2e89c79e2ac0f2d0f292c668df6bff76b9a0`).
