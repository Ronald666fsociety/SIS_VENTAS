<template>
  <q-page class="q-pa-md">
    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando información de pago...</div>
    </div>

    <div v-else-if="pedido">
      <!-- Tarjeta superior de monto -->
      <q-card flat bordered class="hero-banner q-mb-md text-center q-pa-md">
        <div class="text-caption text-indigo-1 text-uppercase text-weight-bold" style="letter-spacing: 0.06em;">
          Monto Exacto a Transferir
        </div>
        <div class="row justify-center items-center q-my-xs q-gutter-x-sm">
          <span class="text-h4 text-weight-bolder text-white tabular-nums font-display">
            Bs {{ pedido.total }}
          </span>
          <q-btn
            flat
            dense
            round
            icon="content_copy"
            color="white"
            @click="copiarMonto"
          >
            <q-tooltip>Copiar monto</q-tooltip>
          </q-btn>
        </div>
        <div class="text-caption text-indigo-1 tabular-nums">Pedido #{{ pedido.codigo }}</div>
      </q-card>

      <!-- Imagen QR grande y datos del banco -->
      <q-card flat bordered class="rounded-borders q-mb-md bg-white shadow-1 text-center q-pa-md">
        <div class="qr-container flex flex-center q-pa-sm bg-grey-1 rounded-borders q-mx-auto" style="max-width: 280px;">
          <img
            :src="configPago?.qr_imagen_url || 'https://placehold.co/300x300/eee/666?text=QR+Estatico'"
            alt="Código QR"
            class="full-width rounded-borders shadow-1"
            style="max-height: 260px; object-fit: contain;"
          />
        </div>

        <q-btn
          outline
          dense
          color="primary"
          icon="download"
          label="Guardar QR"
          class="q-mt-sm q-px-md"
          @click="descargarQR"
        />

        <!-- Datos del banco -->
        <div v-if="configPago?.banco || configPago?.titular" class="q-mt-md text-left bg-grey-1 q-pa-sm rounded-borders">
          <div v-if="configPago.banco" class="text-caption text-grey-8">
            <b>Banco:</b> {{ configPago.banco }}
          </div>
          <div v-if="configPago.titular" class="text-caption text-grey-8">
            <b>Titular:</b> {{ configPago.titular }}
          </div>
          <div v-if="configPago.nro_cuenta" class="text-caption text-grey-8">
            <b>Nro. Cuenta:</b> {{ configPago.nro_cuenta }}
          </div>
        </div>
      </q-card>

      <!-- Instrucciones en 3 pasos -->
      <q-card flat bordered class="rounded-borders q-mb-md bg-white shadow-1">
        <q-card-section>
          <div class="text-subtitle2 text-weight-bold q-mb-sm">Pasos para completar tu compra:</div>
          <div class="column q-gutter-y-xs text-body2 text-grey-8">
            <div class="row items-center">
              <q-badge rounded color="primary" label="1" class="q-mr-sm" />
              <span>Paga desde tu app bancaria escaneando el QR.</span>
            </div>
            <div class="row items-center">
              <q-badge rounded color="primary" label="2" class="q-mr-sm" />
              <span>Toma una captura de pantalla del comprobante.</span>
            </div>
            <div class="row items-center">
              <q-badge rounded color="primary" label="3" class="q-mr-sm" />
              <span>Súbela aquí para validar tu pedido.</span>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Aviso de verificación en minutos -->
      <q-banner dense rounded class="bg-blue-1 text-primary q-mb-md">
        <template #avatar>
          <q-icon name="schedule" color="primary" />
        </template>
        <span class="text-weight-medium">
          Verificamos tu pago en menos de {{ configPago?.verificacion_minutos || 15 }} minutos.
        </span>
      </q-banner>

      <!-- Alerta si hubo un rechazo previo -->
      <q-banner
        v-if="pedido.pago?.motivo_rechazo"
        dense
        rounded
        class="bg-red-1 text-negative q-mb-md"
      >
        <template #avatar>
          <q-icon name="error" color="negative" />
        </template>
        <div>
          <b>Tu comprobante anterior fue rechazado:</b>
          <div>{{ pedido.pago.motivo_rechazo }}</div>
          <div class="text-caption q-mt-xs">
            Te quedan {{ pedido.pago.intentos_restantes ?? 1 }} intento(s) para subir un comprobante válido.
          </div>
        </div>
      </q-banner>

      <!-- Formulario de subida de comprobante -->
      <q-card flat bordered class="rounded-borders bg-white shadow-2 q-pa-md q-mb-lg">
        <div class="text-subtitle1 text-weight-bold q-mb-sm">Subir Comprobante de Pago</div>

        <div class="row q-gutter-xs items-start q-mb-sm">
          <q-file
            v-model="archivoComprobante"
            outlined
            dense
            label="Subir captura del comprobante *"
            accept="image/*"
            bottom-slots
            class="col"
            @update:model-value="generarPreview"
          >
            <template #prepend>
              <q-icon name="attach_file" />
            </template>
            <template #append>
              <q-icon
                v-if="archivoComprobante"
                name="close"
                @click.stop="limpiarComprobante"
                class="cursor-pointer"
              />
            </template>
          </q-file>
          <q-btn
            outline
            dense
            no-caps
            color="primary"
            icon="add_a_photo"
            label="Cámara"
            class="q-px-sm"
            style="height: 40px;"
            @click="tomarFotoComprobante"
          />
        </div>

        <!-- Vista previa de imagen -->
        <div v-if="previewUrl" class="text-center q-my-sm">
          <img
            :src="previewUrl"
            alt="Vista previa comprobante"
            class="rounded-borders shadow-1"
            style="max-height: 220px; max-width: 100%; object-fit: contain;"
          />
        </div>

        <q-input
          v-model="nroTransaccion"
          outlined
          dense
          label="Número de transacción / referencia bancaria"
          placeholder="Ej: 12345678"
          class="q-mt-sm"
          hint="Opcional pero acelera la verificación de tu pago"
        />

        <q-btn
          unelevated
          color="positive"
          label="Enviar comprobante"
          icon="upload"
          :loading="subiendo"
          :disable="!archivoComprobante"
          class="full-width text-weight-bolder q-mt-md shadow-2"
          style="min-height: 48px; font-size: 16px;"
          @click="enviarComprobante"
        />

        <div class="row justify-center q-mt-sm">
          <q-btn
            flat
            dense
            no-caps
            color="grey-7"
            label="Ver seguimiento del pedido"
            :to="`/pedido/${pedido.id}`"
          />
        </div>
      </q-card>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../../shared/api/client';
import { Notify } from 'quasar';
import type { Pedido, ConfiguracionPago } from '../../shared/types';
import { comprimirImagen, capturarFotoNativa } from '../../shared/utils/imagen';

const route = useRoute();
const router = useRouter();

const pedidoId = route.params.id as string;
const pedido = ref<Pedido | null>(null);
const configPago = ref<ConfiguracionPago | null>(null);
const cargando = ref(true);

const archivoComprobante = ref<File | null>(null);
const previewUrl = ref<string | null>(null);
const nroTransaccion = ref('');
const subiendo = ref(false);

onMounted(async () => {
  await Promise.all([cargarPedido(), cargarConfigPago()]);
  cargando.value = false;
});

async function cargarPedido() {
  try {
    const resp = await api.get(`/pedidos/${pedidoId}`);
    pedido.value = resp.data.data;
  } catch (e: any) {
    Notify.create({
      type: 'negative',
      message: 'No se pudo cargar el pedido',
    });
  }
}

async function cargarConfigPago() {
  try {
    const resp = await api.get('/configuracion-pago');
    configPago.value = resp.data;
  } catch (e) {
    console.error('Error al cargar config de pago', e);
  }
}

function copiarMonto() {
  if (!pedido.value) return;
  navigator.clipboard.writeText(String(pedido.value.total));
  Notify.create({
    type: 'positive',
    message: `Monto Bs ${pedido.value.total} copiado al portapapeles`,
    timeout: 1500,
  });
}

function descargarQR() {
  if (!configPago.value?.qr_imagen_url) return;
  window.open(configPago.value.qr_imagen_url, '_blank');
}

function generarPreview(file: File | null) {
  if (file) {
    previewUrl.value = URL.createObjectURL(file);
  } else {
    previewUrl.value = null;
  }
}

function limpiarComprobante() {
  archivoComprobante.value = null;
  previewUrl.value = null;
}

async function tomarFotoComprobante() {
  const file = await capturarFotoNativa();
  if (file) {
    archivoComprobante.value = file;
    generarPreview(file);
  }
}

async function enviarComprobante() {
  if (!archivoComprobante.value || !pedido.value) return;

  subiendo.value = true;
  try {
    const comprobanteOptimizado = await comprimirImagen(archivoComprobante.value);
    const formData = new FormData();
    formData.append('comprobante', comprobanteOptimizado);
    if (nroTransaccion.value.trim()) {
      formData.append('nro_transaccion', nroTransaccion.value.trim());
    }

    await api.post(`/pedidos/${pedido.value.id}/pago/comprobante`, formData);

    Notify.create({
      type: 'positive',
      message: 'Comprobante recibido. Estamos verificando tu pago.',
    });

    router.replace(`/pedido/${pedido.value.id}`);
  } catch (err: any) {
    const errObj = err.response?.data?.errors;
    const firstErr = errObj ? Object.values(errObj)[0] : null;
    const msg = (Array.isArray(firstErr) ? firstErr[0] : firstErr) ||
      err.response?.data?.message ||
      'Error al subir el comprobante. Inténtalo de nuevo.';
    Notify.create({
      type: 'negative',
      message: msg,
    });
  } finally {
    subiendo.value = false;
  }
}
</script>
