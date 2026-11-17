<template>
  <q-page class="q-pa-md">
    <div class="row justify-between items-center q-mb-md">
      <div>
        <div class="text-h6 text-weight-bolder text-grey-9">Administración</div>
        <div class="text-caption text-grey-6">Configuración, zonas, usuarios y reportes</div>
      </div>
    </div>

    <!-- Menú de opciones de administración -->
    <q-card flat bordered class="bg-white q-mb-md overflow-hidden">
      <q-list separator>
        <!-- Reportes de Ventas -->
        <q-item clickable v-ripple class="q-py-md" @click="abrirReportes = true">
          <q-item-section avatar>
            <q-avatar color="indigo-1" text-color="primary" icon="insights" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-10">Reportes de Ventas</q-item-label>
            <q-item-label caption>Resumen de ventas y exportar PDF / Excel</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>

        <!-- Configuración de Pago QR -->
        <q-item v-if="authStore.esAdmin" clickable v-ripple class="q-py-md" @click="abrirConfigPago = true">
          <q-item-section avatar>
            <q-avatar color="blue-1" text-color="indigo-8" icon="qr_code_2" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-10">Configuración de Pago (QR / Efectivo)</q-item-label>
            <q-item-label caption>Cambiar QR estático, cuenta bancaria y pago contra entrega</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>

        <!-- Zonas de Envío -->
        <q-item v-if="authStore.esAdmin" clickable v-ripple class="q-py-md" @click="abrirZonas = true">
          <q-item-section avatar>
            <q-avatar color="teal-1" text-color="teal-8" icon="map" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-10">Zonas de Envío</q-item-label>
            <q-item-label caption>Tarifas de envío, cobertura nacional y pedido mínimo</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>

        <!-- Usuarios y Repartidores -->
        <q-item v-if="authStore.esAdmin" clickable v-ripple class="q-py-md" @click="abrirUsuarios = true">
          <q-item-section avatar>
            <q-avatar color="amber-1" text-color="amber-9" icon="group" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-10">Personal y Repartidores</q-item-label>
            <q-item-label caption>Crear repartidores y gestionar accesos</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>

        <!-- Bitácora de Auditoría -->
        <q-item v-if="authStore.esAdmin" clickable v-ripple class="q-py-md" @click="abrirAuditoria = true">
          <q-item-section avatar>
            <q-avatar color="grey-2" text-color="grey-8" icon="history" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-10">Auditoría y Seguridad</q-item-label>
            <q-item-label caption>Registro de cambios de stock, precios y pagos</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>

        <!-- Configuración de servidor API -->
        <q-item clickable v-ripple class="q-py-md" @click="abrirServidor = true">
          <q-item-section avatar>
            <q-avatar color="indigo-1" text-color="primary" icon="dns" />
          </q-item-section>
          <q-item-section>
            <q-item-label class="text-weight-bold text-grey-10">Servidor de Datos (Internet / Wi-Fi)</q-item-label>
            <q-item-label caption>Cambiar entre IP local Wi-Fi o túnel de Internet</q-item-label>
          </q-item-section>
          <q-item-section side>
            <q-icon name="chevron_right" color="grey-5" />
          </q-item-section>
        </q-item>
      </q-list>
    </q-card>

    <q-btn
      outline
      color="negative"
      icon="logout"
      label="Cerrar Sesión de Administrador"
      class="full-width text-weight-bold q-mt-md"
      style="min-height: 48px;"
      @click="cerrarSesion"
    />

    <!-- Diálogo: Reportes de Ventas -->
    <q-dialog v-model="abrirReportes" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Reporte de Ventas</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-6">
              <q-input v-model="fechaDesde" type="date" outlined dense label="Desde" />
            </div>
            <div class="col-6">
              <q-input v-model="fechaHasta" type="date" outlined dense label="Hasta" />
            </div>
          </div>

          <q-btn
            unelevated
            color="primary"
            label="Consultar Ventas"
            icon="analytics"
            class="full-width q-mb-md text-weight-bold"
            @click="cargarReporteVentas"
          />

          <!-- Resumen de ventas -->
          <div v-if="reporteDatos" class="column q-gutter-y-sm">
            <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md">
              <div class="text-caption text-grey-7 text-uppercase text-weight-bold">Total Ventas</div>
              <div class="text-h4 text-weight-bolder text-primary">
                Bs {{ reporteDatos.resumen?.ventas || '0.00' }}
              </div>
              <div class="text-caption text-grey-8">
                Pedidos realizados: <b>{{ reporteDatos.resumen?.pedidos || 0 }}</b> · Ticket prom: <b>Bs {{ reporteDatos.resumen?.ticket_promedio }}</b>
              </div>
            </q-card>

            <!-- Botones exportar PDF / Excel -->
            <div class="row q-gutter-x-sm q-my-sm">
              <q-btn
                unelevated
                color="red-8"
                icon="picture_as_pdf"
                label="Descargar PDF"
                class="col text-weight-bold"
                @click="descargarArchivoReporte('pdf')"
              />
              <q-btn
                unelevated
                color="positive"
                icon="table_chart"
                label="Descargar Excel"
                class="col text-weight-bold"
                @click="descargarArchivoReporte('xlsx')"
              />
            </div>

            <!-- Top productos vendidos -->
            <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md">
              <div class="text-subtitle2 text-weight-bold q-mb-xs">Productos más vendidos</div>
              <q-list separator>
                <q-item v-for="(p, i) in reporteDatos.por_producto" :key="i" dense class="q-px-none">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ p.producto }}</q-item-label>
                    <q-item-label caption>{{ p.unidades }} unidades</q-item-label>
                  </q-item-section>
                  <q-item-section side class="text-weight-bold text-primary">
                    Bs {{ p.total }}
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Configuración de Pago QR -->
    <q-dialog v-model="abrirConfigPago" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Configuración de Pago</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md q-gutter-y-sm">
            <div class="text-subtitle2 text-weight-bold q-mb-xs">Imagen del QR Estático</div>

            <div v-if="configPagoActual?.qr_imagen_url" class="text-center q-my-sm">
              <img
                :src="configPagoActual.qr_imagen_url"
                alt="QR actual"
                style="max-height: 200px; max-width: 100%; object-fit: contain;"
                class="rounded-borders shadow-1"
              />
            </div>

            <q-file
              v-model="nuevoQRFile"
              outlined
              dense
              label="Subir nueva imagen de QR (PNG/JPG)"
              accept="image/*"
            >
              <template #prepend>
                <q-icon name="qr_code" />
              </template>
            </q-file>

            <q-input v-model="formPago.banco" outlined dense label="Nombre del Banco" />
            <q-input v-model="formPago.titular" outlined dense label="Titular de la Cuenta" />
            <q-input v-model="formPago.nro_cuenta" outlined dense label="Número de Cuenta Bancaria" />
            <q-input
              v-model="formPago.instrucciones"
              outlined
              dense
              type="textarea"
              rows="3"
              label="Instrucciones al cliente"
            />

            <q-toggle
              v-model="formPago.aceptar_efectivo"
              color="positive"
              label="Permitir pago en efectivo contra entrega"
            />

            <q-btn
              unelevated
              color="primary"
              label="Guardar Configuración de Pago"
              class="full-width text-weight-bold q-mt-md"
              style="min-height: 48px;"
              @click="guardarConfigPago"
            />
          </q-card>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Zonas de Envío (Configuración completa y cobertura en toda Bolivia) -->
    <q-dialog v-model="abrirZonas" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Zonas de Envío</q-toolbar-title>
          <q-btn flat round dense icon="add_location_alt" @click="abrirNuevaZona">
            <q-tooltip>Nueva Zona</q-tooltip>
          </q-btn>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <div class="row q-gutter-sm q-mb-md">
            <q-btn
              unelevated
              no-caps
              color="primary"
              icon="add"
              label="Nueva Zona / Ciudad"
              class="col text-weight-bold"
              @click="abrirNuevaZona"
            />
            <q-btn
              outline
              no-caps
              color="teal-8"
              icon="public"
              label="Preset Toda Bolivia"
              class="col text-weight-bold"
              @click="abrirPresetNacional"
            />
          </div>

          <q-list separator class="bg-white rounded-borders shadow-1">
            <q-item v-for="z in listaZonas" :key="z.id" class="q-py-md">
              <q-item-section avatar>
                <q-icon :name="(z.radio_km || 0) >= 500 ? 'public' : 'place'" color="primary" size="28px" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold text-subtitle2">{{ z.nombre }}</q-item-label>
                <q-item-label caption>Envío: <b>Bs {{ z.costo_envio }}</b> · Mínimo: <b>Bs {{ z.pedido_minimo }}</b></q-item-label>
                <q-item-label caption>Radio cobertura: <b>{{ z.radio_km || 5 }} km</b></q-item-label>
              </q-item-section>
              <q-item-section side>
                <div class="row items-center q-gutter-x-xs">
                  <q-toggle
                    v-model="z.activa"
                    color="positive"
                    dense
                    @update:model-value="toggleZonaActiva(z)"
                  />
                  <q-btn flat round dense icon="edit" color="primary" size="sm" @click="editarZona(z)" />
                  <q-btn flat round dense icon="delete_outline" color="negative" size="sm" @click="eliminarZona(z)" />
                </div>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo para crear / editar una zona de envío -->
    <q-dialog v-model="modalEditarZona">
      <q-card class="rounded-borders q-pa-md" style="min-width: 330px; max-width: 95vw;">
        <div class="text-h6 text-weight-bold q-mb-sm">
          {{ zonaEditando ? 'Editar Zona de Envío' : 'Nueva Zona de Envío' }}
        </div>

        <q-form @submit="guardarZona" class="q-gutter-y-sm">
          <q-select
            v-model="presetCiudadZona"
            :options="[{ nombre: 'Toda Bolivia (Nacional)', lat: CENTRO_BOLIVIA.lat, lng: CENTRO_BOLIVIA.lng, radioSugeridoKm: 1200 }, ...CIUDADES_BOLIVIA]"
            option-label="nombre"
            outlined
            dense
            label="Autocompletar ubicación (opcional)"
            clearable
            @update:model-value="aplicarPresetCiudadZona"
          />

          <q-btn
            outline
            dense
            no-caps
            color="primary"
            icon="my_location"
            label="Usar mi ubicación GPS actual"
            class="full-width"
            @click="usarMiUbicacionEnZona"
          />

          <q-input
            v-model="formZona.nombre"
            outlined
            dense
            label="Nombre de la zona *"
            placeholder="Ej: La Paz, Cochabamba, Toda Bolivia..."
            :rules="[val => !!val || 'Obligatorio']"
          />

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-input
                v-model.number="formZona.costo_envio"
                type="number"
                step="0.5"
                outlined
                dense
                label="Costo envío (Bs) *"
                :rules="[val => val >= 0 || 'Inválido']"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model.number="formZona.pedido_minimo"
                type="number"
                step="1"
                outlined
                dense
                label="Pedido mínimo (Bs) *"
              />
            </div>
          </div>

          <q-input
            v-model.number="formZona.radio_km"
            type="number"
            step="1"
            outlined
            dense
            label="Radio de cobertura (km) *"
            hint="Ej: 15 km para una ciudad, 1200 km para toda Bolivia"
            :rules="[val => val > 0 && val <= 5000 || 'Entre 1 y 5000 km']"
          />

          <div class="row q-col-gutter-sm">
            <div class="col-6">
              <q-input
                v-model.number="formZona.centro_lat"
                type="number"
                step="0.0001"
                outlined
                dense
                label="Latitud centro *"
              />
            </div>
            <div class="col-6">
              <q-input
                v-model.number="formZona.centro_lng"
                type="number"
                step="0.0001"
                outlined
                dense
                label="Longitud centro *"
              />
            </div>
          </div>

          <q-toggle v-model="formZona.activa" label="Zona activa para recibir pedidos" color="positive" />

          <q-card-actions align="right" class="q-mt-md">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn unelevated color="primary" label="Guardar Zona" type="submit" :loading="guardandoZona" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Usuarios y Repartidores -->
    <q-dialog v-model="abrirUsuarios" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Personal y Repartidores</q-toolbar-title>
          <q-btn flat round dense icon="person_add" @click="abrirNuevoUsuario = true" />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <q-list separator class="bg-white rounded-borders shadow-1">
            <q-item v-for="u in listaUsuarios" :key="u.id" class="q-py-md">
              <q-item-section avatar>
                <q-avatar color="primary" text-color="white" size="42px">
                  {{ u.nombre.charAt(0) }}
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ u.nombre }} {{ u.apellido || '' }}</q-item-label>
                <q-item-label caption>{{ u.telefono }}</q-item-label>
                <q-item-label caption class="text-primary text-weight-bold text-uppercase">{{ u.rol }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-toggle v-model="u.activo" color="positive" @update:model-value="toggleUsuarioActivo(u)" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo para crear nuevo repartidor / operador -->
    <q-dialog v-model="abrirNuevoUsuario">
      <q-card class="rounded-borders q-pa-md" style="min-width: 320px; max-width: 90vw;">
        <div class="text-h6 text-weight-bold q-mb-sm">Nuevo Miembro del Personal</div>
        <q-form @submit="crearUsuarioPersonal" class="q-gutter-y-sm">
          <q-select
            v-model="formNuevoUser.rol"
            :options="['repartidor', 'operador', 'admin']"
            outlined
            dense
            label="Rol *"
          />
          <q-input v-model="formNuevoUser.nombre" outlined dense label="Nombre *" :rules="[val => !!val || 'Obligatorio']" />
          <q-input v-model="formNuevoUser.telefono" outlined dense label="Teléfono Celular *" :rules="[val => !!val || 'Obligatorio']" />
          <q-input v-model="formNuevoUser.password" outlined dense type="password" label="Contraseña *" :rules="[val => val.length >= 8 || 'Mínimo 8 caracteres']" />

          <q-card-actions align="right" class="q-mt-md">
            <q-btn flat label="Cancelar" v-close-popup />
            <q-btn unelevated color="primary" label="Crear" type="submit" />
          </q-card-actions>
        </q-form>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Auditoría -->
    <q-dialog v-model="abrirAuditoria" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Bitácora de Auditoría</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <q-list separator class="bg-white rounded-borders shadow-1">
            <q-item v-for="a in listaAuditoria" :key="a.id">
              <q-item-section>
                <q-item-label class="text-weight-bold text-primary">{{ a.accion }}</q-item-label>
                <q-item-label caption>
                  Por: <b>{{ a.usuario?.nombre || 'Sistema' }}</b> ({{ a.usuario?.rol || 'N/A' }})
                </q-item-label>
                <q-item-label caption class="text-grey-7" style="word-break: break-all;">
                  {{ a.detalle ? JSON.stringify(a.detalle) : '' }}
                </q-item-label>
                <q-item-label caption class="text-grey-5">{{ a.created_at }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>
    <ServerConfigDialog v-model="abrirServidor" />
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../../shared/api/client';
import { useAuthStore } from '../../shared/stores/auth';
import { Notify } from 'quasar';
import { CIUDADES_BOLIVIA, CENTRO_BOLIVIA, type CiudadBolivia } from '../../shared/utils/bolivia';
import { descargarBlobArchivo } from '../../shared/utils/descargar';
import { comprimirImagen } from '../../shared/utils/imagen';
import { obtenerGpsActual } from '../../shared/utils/ubicacion';
import ServerConfigDialog from '../../shared/components/ServerConfigDialog.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const abrirReportes = ref(false);
const abrirConfigPago = ref(false);
const abrirZonas = ref(false);
const abrirUsuarios = ref(false);
const abrirAuditoria = ref(false);
const abrirNuevoUsuario = ref(false);
const abrirServidor = ref(false);

const fechaDesde = ref(new Date().toISOString().substring(0, 10));
const fechaHasta = ref(new Date().toISOString().substring(0, 10));
const reporteDatos = ref<any | null>(null);

const configPagoActual = ref<any | null>(null);
const nuevoQRFile = ref<File | null>(null);
const formPago = ref({ banco: '', titular: '', nro_cuenta: '', instrucciones: '', aceptar_efectivo: true });

const listaZonas = ref<any[]>([]);
const modalEditarZona = ref(false);
const zonaEditando = ref<any | null>(null);
const guardandoZona = ref(false);
const presetCiudadZona = ref<CiudadBolivia | null>(null);
const formZona = ref({
  nombre: '',
  costo_envio: 15,
  pedido_minimo: 30,
  radio_km: 25,
  centro_lat: -17.3935,
  centro_lng: -66.1570,
  activa: true,
});
const listaUsuarios = ref<any[]>([]);
const listaAuditoria = ref<any[]>([]);

const formNuevoUser = ref({
  nombre: '',
  telefono: '',
  password: '',
  rol: 'repartidor',
});

onMounted(() => {
  if (route.query.seccion === 'reportes') {
    abrirReportes.value = true;
  }
  cargarConfigPago();
  cargarZonas();
  cargarUsuarios();
  cargarAuditoria();
  cargarReporteVentas();
});

async function cargarReporteVentas() {
  try {
    const resp = await api.get('/admin/reportes/ventas', {
      params: { desde: fechaDesde.value, hasta: fechaHasta.value, formato: 'json' },
    });
    reporteDatos.value = resp.data;
  } catch (e) {}
}

async function descargarArchivoReporte(formato: 'pdf' | 'xlsx') {
  try {
    const resp = await api.get('/admin/reportes/ventas', {
      params: { desde: fechaDesde.value, hasta: fechaHasta.value, formato },
      responseType: 'blob',
    });
    await descargarBlobArchivo(resp.data, `ventas_${fechaDesde.value}_${fechaHasta.value}.${formato}`);
  } catch (e) {
    Notify.create({ type: 'negative', message: 'No se pudo descargar el reporte' });
  }
}

async function cargarConfigPago() {
  try {
    const resp = await api.get('/admin/configuracion-pago');
    configPagoActual.value = resp.data;
    if (resp.data) {
      formPago.value = {
        banco: resp.data.banco || '',
        titular: resp.data.titular || '',
        nro_cuenta: resp.data.nro_cuenta || '',
        instrucciones: resp.data.instrucciones || '',
        aceptar_efectivo: resp.data.aceptar_efectivo !== false,
      };
    }
  } catch (e) {}
}

async function guardarConfigPago() {
  try {
    const formData = new FormData();
    formData.append('banco', formPago.value.banco);
    formData.append('titular', formPago.value.titular);
    formData.append('nro_cuenta', formPago.value.nro_cuenta);
    formData.append('instrucciones', formPago.value.instrucciones);
    formData.append('aceptar_efectivo', formPago.value.aceptar_efectivo ? '1' : '0');
    if (nuevoQRFile.value) {
      const qrOptimizado = await comprimirImagen(nuevoQRFile.value);
      formData.append('qr_imagen', qrOptimizado);
    }
    await api.post('/admin/configuracion-pago', formData);
    Notify.create({ type: 'positive', message: 'Configuración de pago actualizada' });
    abrirConfigPago.value = false;
    await cargarConfigPago();
  } catch (err: any) {
    Notify.create({ type: 'negative', message: 'Error al actualizar configuración' });
  }
}

async function cargarZonas() {
  try {
    const resp = await api.get('/admin/zonas');
    listaZonas.value = resp.data.data;
  } catch (e) {}
}

function abrirNuevaZona() {
  zonaEditando.value = null;
  presetCiudadZona.value = null;
  formZona.value = {
    nombre: '',
    costo_envio: 15,
    pedido_minimo: 30,
    radio_km: 25,
    centro_lat: -17.3935,
    centro_lng: -66.1570,
    activa: true,
  };
  modalEditarZona.value = true;
}

function abrirPresetNacional() {
  zonaEditando.value = null;
  presetCiudadZona.value = null;
  formZona.value = {
    nombre: CENTRO_BOLIVIA.nombre,
    costo_envio: 25,
    pedido_minimo: 30,
    radio_km: CENTRO_BOLIVIA.radioKm,
    centro_lat: CENTRO_BOLIVIA.lat,
    centro_lng: CENTRO_BOLIVIA.lng,
    activa: true,
  };
  modalEditarZona.value = true;
}

function editarZona(z: any) {
  zonaEditando.value = z;
  presetCiudadZona.value = null;
  formZona.value = {
    nombre: z.nombre,
    costo_envio: parseFloat(String(z.costo_envio || 0)),
    pedido_minimo: parseFloat(String(z.pedido_minimo || 0)),
    radio_km: parseFloat(String(z.radio_km || 5)),
    centro_lat: parseFloat(String(z.centro_lat ?? -17.3935)),
    centro_lng: parseFloat(String(z.centro_lng ?? -66.1570)),
    activa: Boolean(z.activa),
  };
  modalEditarZona.value = true;
}

function aplicarPresetCiudadZona(c: CiudadBolivia | null) {
  if (!c) return;
  formZona.value.nombre = c.nombre;
  formZona.value.centro_lat = c.lat;
  formZona.value.centro_lng = c.lng;
  if (c.radioSugeridoKm) {
    formZona.value.radio_km = c.radioSugeridoKm;
  }
}

async function usarMiUbicacionEnZona() {
  const gps = await obtenerGpsActual(10000);
  if (!gps) {
    Notify.create({ type: 'warning', message: 'No se pudo obtener tu GPS actual.' });
    return;
  }
  formZona.value.centro_lat = Number(gps.lat.toFixed(6));
  formZona.value.centro_lng = Number(gps.lng.toFixed(6));
  Notify.create({ type: 'positive', message: 'Coordenadas tomadas desde tu GPS actual', timeout: 1200 });
}

async function guardarZona() {
  guardandoZona.value = true;
  try {
    if (zonaEditando.value) {
      await api.put(`/admin/zonas/${zonaEditando.value.id}`, formZona.value);
      Notify.create({ type: 'positive', message: 'Zona de envío actualizada' });
    } else {
      await api.post('/admin/zonas', formZona.value);
      Notify.create({ type: 'positive', message: 'Zona de envío creada' });
    }
    modalEditarZona.value = false;
    await cargarZonas();
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error al guardar zona' });
  } finally {
    guardandoZona.value = false;
  }
}

async function toggleZonaActiva(z: any) {
  try {
    await api.put(`/admin/zonas/${z.id}`, { activa: z.activa });
    Notify.create({ type: 'info', message: `Zona ${z.activa ? 'activada' : 'desactivada'}`, timeout: 900 });
  } catch (e) {
    z.activa = !z.activa;
  }
}

async function eliminarZona(z: any) {
  try {
    const resp = await api.delete(`/admin/zonas/${z.id}`);
    Notify.create({ type: 'info', message: resp.data?.message || 'Zona eliminada' });
    await cargarZonas();
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error al eliminar zona' });
  }
}

async function cargarUsuarios() {
  try {
    const resp = await api.get('/admin/usuarios');
    listaUsuarios.value = resp.data.data;
  } catch (e) {}
}

async function toggleUsuarioActivo(u: any) {
  try {
    await api.put(`/admin/usuarios/${u.id}`, { activo: u.activo });
  } catch (e) {
    u.activo = !u.activo;
  }
}

async function crearUsuarioPersonal() {
  try {
    await api.post('/admin/usuarios', formNuevoUser.value);
    Notify.create({ type: 'positive', message: 'Usuario creado exitosamente' });
    abrirNuevoUsuario.value = false;
    await cargarUsuarios();
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error al crear usuario' });
  }
}

async function cargarAuditoria() {
  try {
    const resp = await api.get('/admin/auditoria');
    listaAuditoria.value = resp.data.data;
  } catch (e) {}
}

async function cerrarSesion() {
  await authStore.logout();
  router.replace('/login');
}
</script>
