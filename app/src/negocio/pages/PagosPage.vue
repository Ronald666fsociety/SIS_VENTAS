<template>
  <q-page class="q-pa-md">
    <div class="row justify-between items-center q-mb-md">
      <div>
        <div class="text-h6 text-weight-bolder text-grey-9">Bandeja de Pagos</div>
        <div class="text-caption text-grey-6">Verificación contra extracto bancario</div>
      </div>
      <q-btn flat round dense icon="refresh" color="grey-7" @click="cargarPagos" />
    </div>

    <!-- Filtro por estado del pago -->
    <q-btn-toggle
      v-model="filtroEstado"
      spread
      no-caps
      rounded
      unelevated
      toggle-color="primary"
      color="grey-3"
      text-color="grey-8"
      :options="[
        { label: 'En revisión', value: 'en_revision' },
        { label: 'Verificados', value: 'verificado' },
        { label: 'Rechazados', value: 'rechazado' },
      ]"
      class="q-mb-md"
      @update:model-value="cargarPagos"
    />

    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando comprobantes...</div>
    </div>

    <!-- Lista vacía -->
    <div v-else-if="pagos.length === 0" class="column items-center justify-center q-pa-xl text-center">
      <q-icon name="check_circle" size="72px" color="positive" />
      <div class="text-h6 text-grey-8 q-mt-md">
        {{ filtroEstado === 'en_revision' ? '¡Todo al día!' : 'No hay pagos con este estado' }}
      </div>
      <div class="text-caption text-grey-6">
        {{ filtroEstado === 'en_revision' ? 'No tienes comprobantes pendientes de verificación.' : '' }}
      </div>
    </div>

    <!-- Tarjetas de pagos -->
    <div v-else class="column q-gutter-y-md">
      <q-card
        v-for="pago in pagos"
        :key="pago.id"
        flat
        bordered
        class="card-interactive bg-white cursor-pointer"
        @click="abrirDetalle(pago)"
      >
        <q-card-section>
          <div class="row justify-between items-center">
            <span class="text-h6 text-weight-bolder text-primary tabular-nums font-display">
              Bs {{ pago.monto }}
            </span>
            <EstadoBadge :estado="pago.estado" />
          </div>

          <div class="text-body2 text-weight-bold text-grey-9 q-mt-xs">
            Pedido #{{ pago.pedido?.codigo || pago.pedido_id }}
          </div>

          <div v-if="pago.pedido?.cliente" class="text-caption text-grey-7">
            Cliente: {{ pago.pedido.cliente.nombre }} ({{ pago.pedido.cliente.telefono }})
          </div>

          <div v-if="pago.nro_transaccion" class="text-caption text-grey-8 text-weight-medium q-mt-xs">
            Nro. Tx: {{ pago.nro_transaccion }}
          </div>

          <div class="row justify-between items-center text-caption text-grey-6 q-mt-xs">
            <span>Intento #{{ pago.intentos }}</span>
            <span>{{ formatearFecha(pago.created_at) }}</span>
          </div>
        </q-card-section>

        <!-- Botones rápidos si está en revisión -->
        <q-card-actions v-if="pago.estado === 'en_revision'" align="between" class="bg-grey-1 q-px-md">
          <q-btn
            unelevated
            color="positive"
            icon="check"
            label="Aprobar"
            size="sm"
            class="text-weight-bold"
            @click.stop="aprobarPago(pago)"
          />
          <q-btn
            unelevated
            color="negative"
            icon="close"
            label="Rechazar"
            size="sm"
            class="text-weight-bold"
            @click.stop="iniciarRechazo(pago)"
          />
          <q-btn
            flat
            dense
            color="primary"
            icon="zoom_in"
            label="Ver"
            size="sm"
            @click.stop="abrirDetalle(pago)"
          />
        </q-card-actions>
      </q-card>
    </div>

    <!-- Modal a pantalla completa para inspección de comprobante -->
    <q-dialog v-model="modalDetalle" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card v-if="pagoSeleccionado" class="column bg-grey-9 text-white">
        <!-- Barra superior con monto esperado -->
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">
            Monto esperado: <span class="text-positive text-h6">Bs {{ pagoSeleccionado.monto }}</span>
          </q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <!-- Visor del comprobante con zoom -->
        <q-card-section class="col flex flex-center q-pa-none overflow-hidden relative-position bg-black">
          <img
            v-if="pagoSeleccionado.comprobante_url"
            :src="pagoSeleccionado.comprobante_url"
            alt="Comprobante de pago"
            class="comprobante-img"
          />
          <div v-else class="text-center text-grey-5 q-pa-xl">
            <q-icon name="image_not_supported" size="64px" />
            <div>No se pudo generar la URL del comprobante</div>
          </div>
        </q-card-section>

        <!-- Datos del cliente y botones de acción fijos -->
        <q-card-section class="bg-dark q-pa-md">
          <div class="row justify-between items-center q-mb-sm text-caption">
            <span>Pedido: <b>#{{ pagoSeleccionado.pedido?.codigo }}</b></span>
            <span v-if="pagoSeleccionado.nro_transaccion">Nro Tx: <b>{{ pagoSeleccionado.nro_transaccion }}</b></span>
          </div>

          <div v-if="pagoSeleccionado.pedido?.cliente" class="row items-center justify-between q-mb-md">
            <div>
              <div class="text-body2 text-weight-bold">{{ pagoSeleccionado.pedido.cliente.nombre }}</div>
              <div class="text-caption text-grey-5">{{ pagoSeleccionado.pedido.cliente.telefono }}</div>
            </div>
            <q-btn
              round
              dense
              color="positive"
              icon="chat"
              @click="abrirWhatsapp(pagoSeleccionado.pedido?.cliente?.whatsapp_url)"
            >
              <q-tooltip>Contactar por WhatsApp</q-tooltip>
            </q-btn>
          </div>

          <!-- Botones GRANDES para Aprobar / Rechazar (zona del pulgar) -->
          <div v-if="pagoSeleccionado.estado === 'en_revision'" class="row q-gutter-x-sm">
            <q-btn
              unelevated
              color="negative"
              icon="close"
              label="Rechazar"
              class="col text-weight-bolder"
              style="min-height: 50px; font-size: 16px;"
              @click="iniciarRechazo(pagoSeleccionado)"
            />
            <q-btn
              unelevated
              color="positive"
              icon="check"
              label="Aprobar"
              class="col text-weight-bolder shadow-2"
              style="min-height: 50px; font-size: 16px;"
              @click="aprobarPago(pagoSeleccionado)"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo de rechazo con opciones rápidas -->
    <q-dialog v-model="modalRechazo">
      <q-card class="rounded-borders q-pa-md" style="min-width: 320px; max-width: 90vw;">
        <div class="text-h6 text-weight-bold text-negative q-mb-xs">Rechazar Pago</div>
        <div class="text-caption text-grey-7 q-mb-md">Elige un motivo para notificar al cliente:</div>

        <div class="column q-gutter-y-xs q-mb-md">
          <q-btn
            v-for="opc in motivosRapidos"
            :key="opc"
            outline
            dense
            color="grey-8"
            :label="opc"
            align="left"
            class="text-weight-medium q-py-xs"
            @click="motivoRechazo = opc"
          />
        </div>

        <q-input
          v-model="motivoRechazo"
          outlined
          dense
          label="Motivo personalizado *"
          placeholder="Escribe el motivo..."
          class="q-mb-md"
        />

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            unelevated
            color="negative"
            label="Confirmar Rechazo"
            :disable="!motivoRechazo.trim()"
            :loading="procesando"
            @click="ejecutarRechazo"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { api } from '../../shared/api/client';
import { Notify } from 'quasar';
import EstadoBadge from '../../shared/components/EstadoBadge.vue';
import type { Pago } from '../../shared/types';

const filtroEstado = ref<'en_revision' | 'verificado' | 'rechazado'>('en_revision');
const pagos = ref<Pago[]>([]);
const cargando = ref(true);
const modalDetalle = ref(false);
const pagoSeleccionado = ref<Pago | null>(null);

const modalRechazo = ref(false);
const pagoARechazar = ref<Pago | null>(null);
const motivoRechazo = ref('');
const procesando = ref(false);

const motivosRapidos = [
  'Monto incorrecto en el comprobante',
  'Comprobante ilegible o borroso',
  'Pago no recibido en la cuenta bancaria',
  'Número de transacción duplicado',
];

onMounted(() => {
  cargarPagos();
});

async function cargarPagos() {
  cargando.value = true;
  try {
    const resp = await api.get('/admin/pagos', {
      params: { estado: filtroEstado.value },
    });
    pagos.value = resp.data.data;
  } catch (e: any) {
    Notify.create({
      type: 'negative',
      message: 'Error al cargar pagos',
    });
  } finally {
    cargando.value = false;
  }
}

async function abrirDetalle(p: Pago) {
  try {
    const resp = await api.get(`/admin/pagos/${p.id}`);
    pagoSeleccionado.value = resp.data.data;
    modalDetalle.value = true;
  } catch (e) {
    Notify.create({ type: 'negative', message: 'No se pudo abrir el detalle del pago' });
  }
}

async function aprobarPago(p: Pago) {
  try {
    await api.post(`/admin/pagos/${p.id}/aprobar`);
    Notify.create({
      type: 'positive',
      message: 'Pago verificado correctamente. Pedido en preparación.',
    });
    modalDetalle.value = false;
    await cargarPagos();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al aprobar el pago',
    });
  }
}

function iniciarRechazo(p: Pago) {
  pagoARechazar.value = p;
  motivoRechazo.value = '';
  modalRechazo.value = true;
}

async function ejecutarRechazo() {
  if (!pagoARechazar.value || !motivoRechazo.value.trim()) return;

  procesando.value = true;
  try {
    await api.post(`/admin/pagos/${pagoARechazar.value.id}/rechazar`, {
      motivo: motivoRechazo.value.trim(),
    });
    Notify.create({
      type: 'warning',
      message: 'Pago rechazado y notificado al cliente.',
    });
    modalRechazo.value = false;
    modalDetalle.value = false;
    await cargarPagos();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al rechazar el pago',
    });
  } finally {
    procesando.value = false;
  }
}

function abrirWhatsapp(url?: string) {
  if (url) window.open(url, '_blank');
}

function formatearFecha(f?: string): string {
  if (!f) return '';
  const d = new Date(f);
  return d.toLocaleString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style scoped>
.comprobante-img {
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
  touch-action: pinch-zoom;
}
</style>
