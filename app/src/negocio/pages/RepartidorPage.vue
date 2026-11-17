<template>
  <q-page class="q-pa-md">
    <div class="row justify-between items-center q-mb-md">
      <div>
        <div class="text-h6 text-weight-bolder text-grey-9">Mis Entregas</div>
        <div class="text-caption text-grey-6">Ruta de entrega y GPS en tiempo real</div>
      </div>
      <q-btn flat round dense icon="refresh" color="grey-7" @click="cargarEntregas" />
    </div>

    <!-- Toggle Activas vs Historial -->
    <q-btn-toggle
      v-model="vistaHistorial"
      spread
      no-caps
      rounded
      unelevated
      toggle-color="primary"
      color="grey-3"
      text-color="grey-8"
      :options="[
        { label: 'Por entregar', value: false },
        { label: 'Historial entregados', value: true },
      ]"
      class="q-mb-md"
      @update:model-value="cargarEntregas"
    />

    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando pedidos...</div>
    </div>

    <!-- Estado vacío -->
    <div v-else-if="pedidos.length === 0" class="column items-center justify-center q-pa-xl text-center">
      <q-icon name="check_circle" size="72px" color="positive" />
      <div class="text-h6 text-grey-8 q-mt-md">
        {{ vistaHistorial ? 'No tienes historial aún' : '¡Sin pedidos pendientes!' }}
      </div>
      <div class="text-caption text-grey-6">
        {{ vistaHistorial ? '' : 'Te avisaremos cuando te asignen un nuevo pedido.' }}
      </div>
    </div>

    <!-- Tarjetas de entregas diseñadas para uso en la calle (texto grande) -->
    <div v-else class="column q-gutter-y-md">
      <q-card
        v-for="p in pedidos"
        :key="p.id"
        flat
        bordered
        class="card-interactive bg-white q-pa-md"
      >
        <div class="row justify-between items-center q-mb-xs">
          <span class="text-h6 text-weight-bolder text-primary tabular-nums font-display">#{{ p.codigo }}</span>
          <EstadoBadge :estado="p.estado" />
        </div>

        <div class="text-subtitle1 text-weight-bold text-grey-10 q-mt-xs">
          {{ p.cliente?.nombre }}
        </div>

        <!-- Dirección destacada -->
        <div class="surface-soft q-pa-sm q-my-sm">
          <div class="section-eyebrow">DIRECCIÓN DE ENTREGA</div>
          <div class="text-body1 text-weight-medium text-grey-10 q-mt-xs">{{ p.direccion_texto }}</div>
          <div v-if="p.referencia" class="text-caption text-grey-7 q-mt-xs">
            <b>Ref:</b> {{ p.referencia }}
          </div>
        </div>

        <!-- Panel de GPS en tiempo real cuando está En Camino -->
        <div v-if="p.estado === 'en_camino'" class="bg-teal-1 rounded-borders q-pa-sm q-mb-md">
          <div class="row items-center justify-between q-mb-xs">
            <span class="text-caption text-weight-bold text-teal-10 row items-center">
              <q-icon name="gps_fixed" size="16px" class="q-mr-xs" />
              Ubicación compartida en tiempo real
            </span>
            <q-badge v-if="p.repartidor_ubicacion" color="teal-9" text-color="white">
              {{ p.repartidor_ubicacion.texto_eta }}
            </q-badge>
          </div>

          <div v-if="p.repartidor_ubicacion" class="text-caption text-teal-10 q-mb-sm">
            Distancia restante al cliente: <b>{{ p.repartidor_ubicacion.distancia_km }} km</b>
          </div>
          <div v-else class="text-caption text-teal-9 q-mb-sm">
            Activa el GPS o pulsa un botón para enviar tu posición al cliente.
          </div>

          <div class="row q-gutter-x-xs">
            <q-btn
              outline
              dense
              no-caps
              size="sm"
              color="teal-9"
              icon="my_location"
              label="Enviar GPS actual"
              class="col"
              @click="enviarGpsPedido(p)"
            />
            <q-btn
              unelevated
              dense
              no-caps
              size="sm"
              color="teal-8"
              icon="fast_forward"
              label="Simular avance"
              class="col"
              @click="simularAvance(p)"
            />
          </div>
        </div>

        <!-- Botones de contacto y navegación rápida -->
        <div class="row q-gutter-x-sm q-mb-md">
          <q-btn
            unelevated
            color="primary"
            icon="phone"
            label="Llamar"
            class="col text-weight-bold"
            :href="`tel:${p.cliente?.telefono}`"
          />
          <q-btn
            unelevated
            color="positive"
            icon="chat"
            label="WhatsApp"
            class="col text-weight-bold"
            @click="abrirWhatsapp(p.cliente?.whatsapp_url)"
          />
          <q-btn
            unelevated
            color="indigo-8"
            icon="navigation"
            label="Ruta"
            class="col text-weight-bold"
            @click="abrirMapa(p.lat, p.lng)"
          />
        </div>

        <!-- Botón SALÍ EN CAMINO si está listo para envío -->
        <q-btn
          v-if="p.estado === 'listo_para_envio'"
          unelevated
          color="teal-8"
          icon="two_wheeler"
          label="Salí en camino al destino"
          class="full-width text-weight-bolder"
          style="min-height: 48px; font-size: 16px;"
          @click="marcarEnCamino(p)"
        />

        <!-- Botón ENTREGAR si está en camino -->
        <div v-else-if="p.estado === 'en_camino'" class="column q-gutter-y-xs">
          <q-btn
            unelevated
            color="positive"
            icon="task_alt"
            label="Entregar al cliente"
            class="full-width text-weight-bolder shadow-2"
            style="min-height: 50px; font-size: 16px;"
            @click="iniciarEntrega(p)"
          />
          <q-btn
            flat
            dense
            color="negative"
            label="No se pudo entregar (fallida)"
            @click="iniciarFallida(p)"
          />
        </div>
      </q-card>
    </div>

    <!-- Diálogo para completar Entrega (Pide código de 4 dígitos + foto) -->
    <q-dialog v-model="modalEntrega">
      <q-card class="rounded-borders q-pa-md" style="min-width: 320px; max-width: 90vw;">
        <div class="text-h6 text-weight-bold text-positive q-mb-xs">Finalizar Entrega</div>
        <div class="text-caption text-grey-7 q-mb-md">
          Pide el código de 4 dígitos al cliente y toma una foto de la entrega:
        </div>

        <q-input
          v-model="codigoEntrega"
          outlined
          dense
          label="Código de 4 dígitos *"
          placeholder="0000"
          type="tel"
          maxlength="4"
          class="text-center text-h5 q-mb-md"
          input-class="text-center text-weight-bolder letter-spacing"
        />

        <q-file
          v-model="fotoEntrega"
          outlined
          dense
          label="Foto de respaldo / entrega *"
          accept="image/*"
          class="q-mb-md"
        >
          <template #prepend>
            <q-icon name="photo_camera" />
          </template>
        </q-file>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            unelevated
            color="positive"
            label="Confirmar Entrega"
            :disable="codigoEntrega.length !== 4 || !fotoEntrega"
            :loading="procesando"
            @click="confirmarEntrega"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo Entrega Fallida -->
    <q-dialog v-model="modalFallida">
      <q-card class="rounded-borders q-pa-md" style="min-width: 320px; max-width: 90vw;">
        <div class="text-h6 text-weight-bold text-negative q-mb-xs">Entrega Fallida</div>
        <div class="text-caption text-grey-7 q-mb-md">Indica el motivo por el cual no se entregó:</div>

        <q-input
          v-model="notaFallida"
          outlined
          dense
          type="textarea"
          rows="3"
          label="Motivo *"
          placeholder="Ej: El cliente no se encontraba en el domicilio..."
          class="q-mb-md"
        />

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            unelevated
            color="negative"
            label="Reportar Fallida"
            :disable="!notaFallida.trim()"
            :loading="procesando"
            @click="confirmarFallida"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { api } from '../../shared/api/client';
import { Notify } from 'quasar';
import EstadoBadge from '../../shared/components/EstadoBadge.vue';
import type { Pedido } from '../../shared/types';

const vistaHistorial = ref(false);
const pedidos = ref<Pedido[]>([]);
const cargando = ref(true);

const modalEntrega = ref(false);
const modalFallida = ref(false);
const pedidoAccion = ref<Pedido | null>(null);
const codigoEntrega = ref('');
const fotoEntrega = ref<File | null>(null);
const notaFallida = ref('');
const procesando = ref(false);

let watchId: number | null = null;
let lastGpsSentAt = 0;

onMounted(() => {
  cargarEntregas();
});

onBeforeUnmount(() => {
  detenerSeguimientoGps();
});

async function cargarEntregas() {
  cargando.value = true;
  try {
    const resp = await api.get('/repartidor/pedidos', {
      params: { historial: vistaHistorial.value ? 1 : 0 },
    });
    pedidos.value = resp.data.data || [];

    const hayEnCamino = pedidos.value.some((p) => p.estado === 'en_camino');
    if (hayEnCamino && !vistaHistorial.value) {
      iniciarSeguimientoGps();
    } else {
      detenerSeguimientoGps();
    }
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar entregas' });
  } finally {
    cargando.value = false;
  }
}

function obtenerPosicionActual(timeoutMs = 3000): Promise<{ lat: number; lng: number } | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      resolve(null);
      return;
    }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => resolve(null),
      { enableHighAccuracy: true, timeout: timeoutMs }
    );
  });
}

function iniciarSeguimientoGps() {
  if (watchId !== null || !navigator.geolocation) return;
  watchId = navigator.geolocation.watchPosition(
    async (pos) => {
      const ahora = Date.now();
      if (ahora - lastGpsSentAt < 8000) return;
      lastGpsSentAt = ahora;

      const enCamino = pedidos.value.filter((p) => p.estado === 'en_camino');
      for (const p of enCamino) {
        try {
          const resp = await api.post(`/repartidor/pedidos/${p.id}/ubicacion`, {
            lat: pos.coords.latitude,
            lng: pos.coords.longitude,
          });
          p.repartidor_ubicacion = resp.data.data?.repartidor_ubicacion || p.repartidor_ubicacion;
        } catch (e) {}
      }
    },
    () => {},
    { enableHighAccuracy: true }
  );
}

function detenerSeguimientoGps() {
  if (watchId !== null && navigator.geolocation) {
    navigator.geolocation.clearWatch(watchId);
    watchId = null;
  }
}

async function marcarEnCamino(p: Pedido) {
  try {
    const gps = await obtenerPosicionActual(3000);
    // Si no hay GPS disponible (ej. navegador de escritorio), partimos a ~2.5 km del destino para iniciar la ruta
    const payload = gps || {
      lat: Number(p.lat) - 0.018,
      lng: Number(p.lng) - 0.015,
    };
    await api.post(`/repartidor/pedidos/${p.id}/en-camino`, payload);
    Notify.create({ type: 'positive', message: 'Marcado en camino. Compartiendo ubicación con el cliente.' });
    await cargarEntregas();
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error' });
  }
}

async function enviarGpsPedido(p: Pedido) {
  const gps = await obtenerPosicionActual(5000);
  if (!gps) {
    Notify.create({
      type: 'warning',
      message: 'No se pudo obtener GPS del navegador. Usa "Simular avance" para probar en PC.',
    });
    return;
  }
  try {
    const resp = await api.post(`/repartidor/pedidos/${p.id}/ubicacion`, gps);
    p.repartidor_ubicacion = resp.data.data?.repartidor_ubicacion || null;
    Notify.create({ type: 'positive', message: 'Ubicación GPS actualizada', timeout: 1000 });
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error al enviar GPS' });
  }
}

async function simularAvance(p: Pedido) {
  const curLat = p.repartidor_ubicacion?.lat ?? Number(p.lat) - 0.025;
  const curLng = p.repartidor_ubicacion?.lng ?? Number(p.lng) - 0.025;
  // Acercar un 30% hacia el destino del cliente en cada toque
  const nuevoLat = curLat + (Number(p.lat) - curLat) * 0.3;
  const nuevoLng = curLng + (Number(p.lng) - curLng) * 0.3;

  try {
    const resp = await api.post(`/repartidor/pedidos/${p.id}/ubicacion`, {
      lat: nuevoLat,
      lng: nuevoLng,
    });
    p.repartidor_ubicacion = resp.data.data?.repartidor_ubicacion || null;
    const eta = p.repartidor_ubicacion?.texto_eta || '';
    const km = p.repartidor_ubicacion?.distancia_km ?? '';
    Notify.create({
      type: 'info',
      message: `Ubicación actualizada · ${km} km (${eta})`,
      timeout: 1200,
    });
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error al actualizar ruta' });
  }
}

function iniciarEntrega(p: Pedido) {
  pedidoAccion.value = p;
  codigoEntrega.value = '';
  fotoEntrega.value = null;
  modalEntrega.value = true;
}

async function confirmarEntrega() {
  if (!pedidoAccion.value || !fotoEntrega.value || codigoEntrega.value.length !== 4) return;

  procesando.value = true;
  try {
    const formData = new FormData();
    formData.append('codigo', codigoEntrega.value);
    formData.append('foto', fotoEntrega.value);

    await api.post(`/repartidor/pedidos/${pedidoAccion.value.id}/entregar`, formData);
    Notify.create({ type: 'positive', message: '¡Pedido entregado con éxito!' });
    modalEntrega.value = false;
    await cargarEntregas();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Código incorrecto o error al registrar foto',
    });
  } finally {
    procesando.value = false;
  }
}

function iniciarFallida(p: Pedido) {
  pedidoAccion.value = p;
  notaFallida.value = '';
  modalFallida.value = true;
}

async function confirmarFallida() {
  if (!pedidoAccion.value || !notaFallida.value.trim()) return;

  procesando.value = true;
  try {
    await api.post(`/repartidor/pedidos/${pedidoAccion.value.id}/fallida`, {
      nota: notaFallida.value.trim(),
    });
    Notify.create({ type: 'warning', message: 'Entrega fallida reportada' });
    modalFallida.value = false;
    await cargarEntregas();
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error' });
  } finally {
    procesando.value = false;
  }
}

function abrirMapa(lat: number, lng: number) {
  window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}

function abrirWhatsapp(url?: string) {
  if (url) window.open(url, '_blank');
}
</script>

<style scoped>
.letter-spacing {
  letter-spacing: 6px;
}
</style>
