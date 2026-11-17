<template>
  <q-page class="q-pa-md">
    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando estado del pedido...</div>
    </div>

    <div v-else-if="pedido">
      <!-- Tarjeta resumen superior -->
      <q-card flat bordered class="q-mb-md bg-white">
        <q-card-section>
          <div class="row justify-between items-center q-mb-xs">
            <span class="text-subtitle1 text-weight-bolder text-grey-10 font-display tabular-nums">
              Pedido #{{ pedido.codigo }}
            </span>
            <EstadoBadge :estado="pedido.estado" :etiqueta="pedido.estado_etiqueta" />
          </div>
          <div class="text-caption text-grey-6">
            Realizado el {{ formatearFecha(pedido.created_at) }}
          </div>
          <div class="row items-center justify-between q-mt-sm">
            <span class="text-h6 text-weight-bolder text-primary tabular-nums font-display">
              Bs {{ pedido.total }}
            </span>
            <q-chip
              dense
              size="sm"
              :color="pedido.pago?.metodo === 'efectivo' ? 'teal-1' : 'indigo-1'"
              :text-color="pedido.pago?.metodo === 'efectivo' ? 'teal-9' : 'primary'"
              :icon="pedido.pago?.metodo === 'efectivo' ? 'payments' : 'qr_code'"
              class="text-weight-bold"
            >
              {{ pedido.pago?.metodo === 'efectivo' ? 'Efectivo contra entrega' : 'Pago por QR' }}
            </q-chip>
          </div>
        </q-card-section>
      </q-card>

      <!-- CALIFICACIÓN DEL PEDIDO Y REPARTIDOR CUANDO YA FUE ENTREGADO -->
      <q-card
        v-if="pedido.estado === 'entregado'"
        flat
        bordered
        class="rounded-borders q-mb-md bg-white shadow-1 q-pa-md"
      >
        <div class="text-subtitle1 text-weight-bold text-grey-9 q-mb-xs row items-center">
          <q-icon name="star" color="amber-8" size="22px" class="q-mr-xs" />
          {{ pedido.calificacion ? 'Tu calificación del servicio' : 'Califica tu pedido y repartidor' }}
        </div>

        <div v-if="pedido.calificacion">
          <q-rating
            :model-value="pedido.calificacion"
            size="28px"
            color="amber-8"
            readonly
            class="q-my-xs"
          />
          <div v-if="pedido.comentario_calificacion" class="text-body2 text-grey-8 q-mt-xs bg-grey-1 q-pa-sm rounded-borders">
            "{{ pedido.comentario_calificacion }}"
          </div>
        </div>

        <div v-else class="column q-gutter-y-sm">
          <div class="text-caption text-grey-7">¿Qué tal estuvo el producto y la entrega?</div>
          <q-rating
            v-model="puntuacion"
            size="34px"
            color="amber-8"
            icon="star_border"
            icon-selected="star"
          />
          <q-input
            v-model="comentarioCalificacion"
            outlined
            dense
            type="textarea"
            rows="2"
            placeholder="Cuéntanos tu experiencia (opcional)..."
          />
          <q-btn
            unelevated
            color="amber-9"
            icon="send"
            label="Enviar calificación"
            :disable="puntuacion < 1"
            :loading="enviandoCalificacion"
            class="text-weight-bold"
            @click="enviarCalificacion"
          />
        </div>
      </q-card>

      <!-- MAPA EN TIEMPO REAL DEL REPARTIDOR + DISTANCIA Y TIEMPO ESTIMADO -->
      <q-card
        v-if="mostrarMapaEnVivo"
        flat
        bordered
        class="rounded-borders q-mb-md bg-white shadow-2 overflow-hidden"
      >
        <q-card-section class="bg-teal-8 text-white q-pa-md">
          <div class="row items-center justify-between">
            <div class="row items-center">
              <q-icon name="two_wheeler" size="26px" class="q-mr-sm" />
              <div>
                <div class="text-caption text-teal-1 text-uppercase text-weight-bold">
                  {{ pedido.estado === 'en_camino' ? 'Repartidor en camino (En vivo)' : 'Preparado para salir' }}
                </div>
                <div class="text-h6 text-weight-bolder">
                  {{ pedido.repartidor_ubicacion ? `Llega en ${pedido.repartidor_ubicacion.texto_eta}` : 'Esperando ubicación del repartidor...' }}
                </div>
              </div>
            </div>
            <q-btn flat round dense icon="refresh" color="white" @click="cargarPedido(true)" />
          </div>

          <div v-if="pedido.repartidor_ubicacion" class="row q-gutter-x-md q-mt-sm text-caption text-teal-1">
            <span>
              <q-icon name="straighten" size="15px" /> Distancia: <b>{{ pedido.repartidor_ubicacion.distancia_km }} km</b>
            </span>
            <span v-if="pedido.repartidor">
              <q-icon name="person" size="15px" /> {{ pedido.repartidor.nombre }}
            </span>
          </div>
        </q-card-section>

        <!-- Contenedor Leaflet -->
        <div ref="trackingMapEl" style="height: 260px; width: 100%;"></div>

        <q-card-section class="q-py-xs q-px-md bg-grey-1 row justify-between items-center text-caption text-grey-7">
          <span>🛵 Repartidor · 📍 Tu entrega</span>
          <span v-if="pedido.repartidor_ubicacion?.actualizado_en">
            Actualizado: {{ formatearHora(pedido.repartidor_ubicacion.actualizado_en) }}
          </span>
        </q-card-section>
      </q-card>

      <!-- Código de entrega si está EN CAMINO -->
      <q-card
        v-if="pedido.estado === 'en_camino' && pedido.codigo_entrega"
        flat
        bordered
        class="bg-amber-1 border-amber rounded-borders q-mb-md text-center q-pa-md shadow-1"
      >
        <div class="text-caption text-weight-bold text-amber-10 text-uppercase">
          Código de Entrega para el Repartidor
        </div>
        <div class="text-h3 text-weight-bolder text-amber-10 letter-spacing q-my-xs">
          {{ pedido.codigo_entrega }}
        </div>
        <div class="text-caption text-grey-8">
          Díctale este código de 4 dígitos al repartidor cuando llegue a tu puerta.
        </div>
      </q-card>

      <!-- Botón de subir comprobante si está pendiente de pago -->
      <q-card
        v-if="pedido.estado === 'pendiente_pago'"
        flat
        bordered
        class="bg-blue-1 rounded-borders q-mb-md text-center q-pa-md shadow-1"
      >
        <div class="text-subtitle2 text-weight-bold text-primary q-mb-xs">
          Pendiente de Comprobante
        </div>
        <div class="text-caption text-grey-8 q-mb-sm">
          Aún no registramos tu pago por QR para este pedido.
        </div>
        <q-btn
          unelevated
          color="primary"
          icon="qr_code"
          label="Pagar por QR y subir comprobante"
          :to="`/pedido/${pedido.id}/pago`"
          class="text-weight-bold"
        />
      </q-card>

      <!-- Línea de tiempo vertical -->
      <q-card flat bordered class="rounded-borders q-mb-md bg-white shadow-1 q-pa-md">
        <div class="text-subtitle1 text-weight-bold q-mb-md">Estado del Pedido</div>

        <q-timeline color="primary" dense>
          <q-timeline-entry
            v-for="(item, idx) in pedido.linea_tiempo"
            :key="idx"
            :title="item.etiqueta"
            :subtitle="formatearFecha(item.fecha)"
            :icon="obtenerIcono(item.estado)"
            :color="obtenerColor(item.estado)"
          >
            <div v-if="item.nota" class="text-caption text-grey-8">
              {{ item.nota }}
            </div>
          </q-timeline-entry>
        </q-timeline>
      </q-card>

      <!-- Productos del pedido -->
      <q-card flat bordered class="rounded-borders q-mb-md bg-white shadow-1">
        <q-expansion-item
          icon="shopping_bag"
          :label="`Ver detalle de productos (${pedido.items?.length || 0})`"
          header-class="text-weight-bold"
        >
          <q-list separator>
            <q-item v-for="(it, i) in pedido.items" :key="i" dense class="q-py-sm">
              <q-item-section>
                <q-item-label class="text-weight-medium">{{ it.nombre_producto }}</q-item-label>
                <q-item-label caption>{{ it.cantidad }} x Bs {{ it.precio_unitario }}</q-item-label>
              </q-item-section>
              <q-item-section side class="text-weight-bold text-primary">
                Bs {{ it.subtotal }}
              </q-item-section>
            </q-item>
          </q-list>
        </q-expansion-item>
      </q-card>

      <!-- Botones de acción: Nota PDF, WhatsApp y Cancelar -->
      <div class="column q-gutter-y-sm q-mb-xl">
        <q-btn
          v-if="pedido.estado !== 'cancelado'"
          unelevated
          color="indigo-8"
          icon="picture_as_pdf"
          label="Descargar Nota de Venta / Comprobante PDF"
          class="full-width text-weight-bold"
          style="min-height: 48px;"
          :loading="descargandoPdf"
          @click="descargarComprobantePdf"
        />

        <q-btn
          v-if="pedido.whatsapp_soporte"
          unelevated
          color="positive"
          icon="chat"
          label="Consultar por WhatsApp"
          class="full-width text-weight-bold"
          style="min-height: 48px;"
          @click="abrirWhatsapp"
        />

        <q-btn
          v-if="esCancelable"
          outline
          color="negative"
          icon="close"
          label="Cancelar pedido"
          class="full-width"
          :loading="cancelando"
          @click="confirmarCancelar"
        />
      </div>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../../shared/api/client';
import { Notify, Dialog } from 'quasar';
import EstadoBadge from '../../shared/components/EstadoBadge.vue';
import { descargarBlobArchivo } from '../../shared/utils/descargar';
import type { Pedido, EstadoPedido } from '../../shared/types';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

const route = useRoute();

const pedidoId = route.params.id as string;
const pedido = ref<Pedido | null>(null);
const cargando = ref(true);
const cancelando = ref(false);
const descargandoPdf = ref(false);
const puntuacion = ref(5);
const comentarioCalificacion = ref('');
const enviandoCalificacion = ref(false);

const trackingMapEl = ref<HTMLElement | null>(null);
let map: L.Map | null = null;
let clienteMarker: L.Marker | null = null;
let repartidorMarker: L.Marker | null = null;
let rutaPolyline: L.Polyline | null = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;
let encuadreInicialHecho = false;

const destinoIcon = L.divIcon({
  className: 'custom-pin-cliente',
  html: '<div style="font-size: 26px; line-height: 1; filter: drop-shadow(0 2px 3px rgba(0,0,0,.4));">📍</div>',
  iconSize: [28, 28],
  iconAnchor: [14, 26],
});

const repartidorIcon = L.divIcon({
  className: 'custom-pin-repartidor',
  html: '<div style="background: #00796b; color: white; border: 2px solid white; border-radius: 50%; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 2px 6px rgba(0,0,0,.4);">🛵</div>',
  iconSize: [34, 34],
  iconAnchor: [17, 17],
});

const mostrarMapaEnVivo = computed(() => {
  if (!pedido.value) return false;
  return ['en_camino', 'listo_para_envio'].includes(pedido.value.estado);
});

const esCancelable = computed(() => {
  if (!pedido.value) return false;
  return ['pendiente_pago', 'pago_en_revision'].includes(pedido.value.estado);
});

onMounted(async () => {
  await cargarPedido();
  iniciarPolling();
});

onBeforeUnmount(() => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
  if (map) {
    map.remove();
    map = null;
  }
});

watch(
  () => [
    mostrarMapaEnVivo.value,
    pedido.value?.repartidor_ubicacion?.lat,
    pedido.value?.repartidor_ubicacion?.lng,
  ],
  async () => {
    if (mostrarMapaEnVivo.value) {
      await nextTick();
      actualizarMapa();
    } else if (map) {
      map.remove();
      map = null;
      clienteMarker = null;
      repartidorMarker = null;
      rutaPolyline = null;
      encuadreInicialHecho = false;
    }
  }
);

function iniciarPolling() {
  if (pollTimer) return;
  pollTimer = setInterval(() => {
    if (pedido.value && !['entregado', 'cancelado'].includes(pedido.value.estado)) {
      cargarPedido(true);
    }
  }, 5000);
}

async function cargarPedido(silencioso = false) {
  try {
    const resp = await api.get(`/pedidos/${pedidoId}`);
    pedido.value = resp.data.data;
  } catch (e: any) {
    if (!silencioso) {
      Notify.create({
        type: 'negative',
        message: 'No se pudo cargar el pedido',
      });
    }
  } finally {
    cargando.value = false;
  }
}

function actualizarMapa() {
  if (!trackingMapEl.value || !pedido.value) return;

  const destLat = Number(pedido.value.lat);
  const destLng = Number(pedido.value.lng);
  if (isNaN(destLat) || isNaN(destLng)) return;

  if (!map) {
    map = L.map(trackingMapEl.value).setView([destLat, destLng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap',
    }).addTo(map);
  }

  if (!clienteMarker) {
    clienteMarker = L.marker([destLat, destLng], { icon: destinoIcon })
      .addTo(map)
      .bindPopup('Tu dirección de entrega');
  } else {
    clienteMarker.setLatLng([destLat, destLng]);
  }

  const rep = pedido.value.repartidor_ubicacion;
  if (rep) {
    const repLat = Number(rep.lat);
    const repLng = Number(rep.lng);

    if (!repartidorMarker) {
      repartidorMarker = L.marker([repLat, repLng], { icon: repartidorIcon })
        .addTo(map)
        .bindPopup(`Repartidor (${rep.texto_eta})`);
    } else {
      repartidorMarker.setLatLng([repLat, repLng]);
      repartidorMarker.setPopupContent(`Repartidor (${rep.texto_eta})`);
    }

    const puntos: L.LatLngExpression[] = [
      [repLat, repLng],
      [destLat, destLng],
    ];
    if (!rutaPolyline) {
      rutaPolyline = L.polyline(puntos, {
        color: '#00796b',
        weight: 4,
        dashArray: '8, 8',
      }).addTo(map);
    } else {
      rutaPolyline.setLatLngs(puntos);
    }

    if (!encuadreInicialHecho) {
      map.fitBounds(L.latLngBounds(puntos), { padding: [36, 36] });
      encuadreInicialHecho = true;
    }
  }
}

function abrirWhatsapp() {
  if (pedido.value?.whatsapp_soporte) {
    window.open(pedido.value.whatsapp_soporte, '_blank');
  }
}

async function descargarComprobantePdf() {
  if (!pedido.value) return;
  descargandoPdf.value = true;
  try {
    const resp = await api.get(`/pedidos/${pedido.value.id}/comprobante-pdf`, {
      responseType: 'blob',
    });
    await descargarBlobArchivo(resp.data, `nota_venta_${pedido.value.codigo}.pdf`);
  } catch (e) {
    Notify.create({
      type: 'negative',
      message: 'No se pudo descargar el comprobante PDF',
    });
  } finally {
    descargandoPdf.value = false;
  }
}

async function enviarCalificacion() {
  if (!pedido.value || puntuacion.value < 1) return;
  enviandoCalificacion.value = true;
  try {
    const resp = await api.post(`/pedidos/${pedido.value.id}/calificar`, {
      calificacion: puntuacion.value,
      comentario: comentarioCalificacion.value.trim() || undefined,
    });
    pedido.value = resp.data.data;
    Notify.create({
      type: 'positive',
      message: '¡Gracias por tu calificación!',
    });
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'No se pudo guardar la calificación',
    });
  } finally {
    enviandoCalificacion.value = false;
  }
}

function confirmarCancelar() {
  Dialog.create({
    title: '¿Cancelar pedido?',
    message: 'Esta acción cancelará tu pedido y devolverá los productos al stock.',
    ok: {
      label: 'Sí, cancelar',
      color: 'negative',
      flat: false,
    },
    cancel: {
      label: 'No, mantener',
      flat: true,
    },
  }).onOk(async () => {
    cancelando.value = true;
    try {
      await api.post(`/pedidos/${pedidoId}/cancelar`);
      Notify.create({
        type: 'positive',
        message: 'Pedido cancelado correctamente',
      });
      await cargarPedido();
    } catch (err: any) {
      Notify.create({
        type: 'negative',
        message: err.response?.data?.message || 'No se pudo cancelar el pedido',
      });
    } finally {
      cancelando.value = false;
    }
  });
}

function formatearFecha(f?: string): string {
  if (!f) return '';
  const d = new Date(f);
  return d.toLocaleString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatearHora(f?: string): string {
  if (!f) return '';
  return new Date(f).toLocaleTimeString('es-BO', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function obtenerIcono(estado: EstadoPedido): string {
  switch (estado) {
    case 'pendiente_pago':
      return 'payment';
    case 'pago_en_revision':
      return 'hourglass_top';
    case 'pago_verificado':
      return 'check_circle';
    case 'preparando':
      return 'inventory_2';
    case 'listo_para_envio':
      return 'local_shipping';
    case 'en_camino':
      return 'two_wheeler';
    case 'entregado':
      return 'done_all';
    case 'cancelado':
      return 'cancel';
    case 'entrega_fallida':
      return 'error';
    default:
      return 'radio_button_checked';
  }
}

function obtenerColor(estado: EstadoPedido): string {
  switch (estado) {
    case 'entregado':
    case 'pago_verificado':
      return 'positive';
    case 'cancelado':
    case 'entrega_fallida':
      return 'negative';
    case 'en_camino':
      return 'teal';
    case 'pendiente_pago':
      return 'amber-9';
    default:
      return 'primary';
  }
}
</script>

<style scoped>
.letter-spacing {
  letter-spacing: 4px;
}
</style>
