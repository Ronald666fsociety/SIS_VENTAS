<template>
  <q-page class="q-pa-md">
    <!-- Paso 1: Selección de dirección -->
    <q-card flat bordered class="q-mb-md rounded-borders">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold q-mb-sm row items-center">
          <q-icon name="place" color="primary" class="q-mr-xs" size="20px" />
          1. Dirección de Entrega
        </div>

        <!-- Pestañas: Guardadas vs Nueva -->
        <q-btn-toggle
          v-if="direccionesGuardadas.length > 0"
          v-model="tipoDireccion"
          spread
          no-caps
          rounded
          unelevated
          toggle-color="primary"
          color="grey-3"
          text-color="grey-8"
          :options="[
            { label: 'Direcciones guardadas', value: 'guardada' },
            { label: 'Nueva ubicación', value: 'nueva' }
          ]"
          class="q-mb-md"
        />

        <!-- Lista de direcciones guardadas -->
        <div v-if="tipoDireccion === 'guardada' && direccionesGuardadas.length > 0">
          <q-list separator>
            <q-item
              v-for="dir in direccionesGuardadas"
              :key="dir.id"
              tag="label"
              v-ripple
              clickable
              class="rounded-borders"
            >
              <q-item-section avatar>
                <q-radio v-model="direccionSeleccionadaId" :val="dir.id" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ dir.alias }}</q-item-label>
                <q-item-label caption>{{ dir.direccion }}</q-item-label>
                <q-item-label v-if="dir.referencia" caption class="text-grey-7">Ref: {{ dir.referencia }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </div>

        <!-- Formulario con mapa para nueva ubicación -->
        <div v-else>
          <MapaPicker
            :initial-lat="lat"
            :initial-lng="lng"
            @ubicacion-cambiada="onUbicacionCambiada"
          />

          <q-input
            v-model="direccionTexto"
            outlined
            dense
            label="Dirección o calle *"
            placeholder="Ej: Av. Bush y 2do Anillo #123"
            class="q-mt-sm"
          />

          <q-input
            v-model="referencia"
            outlined
            dense
            label="Referencia adicional (opcional)"
            placeholder="Ej: Portón café, al lado de la farmacia"
            class="q-mt-sm"
          />

          <q-checkbox
            v-model="guardarDireccion"
            label="Guardar esta dirección en mi cuenta"
            dense
            class="q-mt-xs"
          />

          <q-input
            v-if="guardarDireccion"
            v-model="alias"
            outlined
            dense
            label="Nombre de la dirección (ej: Casa, Trabajo)"
            class="q-mt-xs"
          />
        </div>
      </q-card-section>
    </q-card>

    <!-- Paso 2: Notas adicionales -->
    <q-card flat bordered class="q-mb-md rounded-borders">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold q-mb-sm row items-center">
          <q-icon name="edit_note" color="primary" class="q-mr-xs" size="20px" />
          2. Notas para el repartidor (opcional)
        </div>
        <q-input
          v-model="notasCliente"
          outlined
          dense
          type="textarea"
          rows="2"
          placeholder="Ej: Tocar el timbre con fuerza, perro amigable..."
        />
      </q-card-section>
    </q-card>

    <!-- Paso 3: Método de Pago y Resumen -->
    <q-card flat bordered class="rounded-borders q-mb-md bg-white shadow-1">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold q-mb-sm row items-center">
          <q-icon name="payments" color="primary" class="q-mr-xs" size="20px" />
          3. Método de Pago
        </div>

        <q-list separator bordered class="rounded-borders">
          <q-item tag="label" v-ripple clickable>
            <q-item-section avatar>
              <q-radio v-model="metodoPago" val="qr" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold">📱 Transferencia / QR Estático</q-item-label>
              <q-item-label caption>Pagas con tu banco y subes el comprobante</q-item-label>
            </q-item-section>
          </q-item>

          <q-item v-if="aceptarEfectivo" tag="label" v-ripple clickable>
            <q-item-section avatar>
              <q-radio v-model="metodoPago" val="efectivo" color="positive" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold">💵 Efectivo Contra Entrega</q-item-label>
              <q-item-label caption>Pagas al repartidor al recibir tu pedido</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-card-section>
    </q-card>

    <!-- Paso 4: Desglose de totales y botón de confirmación -->
    <q-card flat bordered class="rounded-borders q-mb-lg bg-white shadow-1">
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold q-mb-sm">Resumen de Pago</div>

        <div class="row justify-between q-py-xs text-grey-8">
          <span>Subtotal de productos:</span>
          <span>Bs {{ carritoStore.subtotal.toFixed(2) }}</span>
        </div>

        <div class="row justify-between q-py-xs text-grey-8">
          <span>Costo de envío:</span>
          <span :class="costoEnvio === 0 ? 'text-positive text-weight-bold' : ''">
            {{ costoEnvio === 0 ? 'Gratis' : `Bs ${costoEnvio.toFixed(2)}` }}
          </span>
        </div>

        <q-separator class="q-my-sm" />

        <div class="row justify-between items-center text-h6 text-weight-bolder text-primary tabular-nums font-display">
          <span>Total a pagar:</span>
          <span>Bs {{ (carritoStore.subtotal + costoEnvio).toFixed(2) }}</span>
        </div>

        <q-banner v-if="!zonaCubre" class="bg-red-1 text-negative q-mt-sm rounded-borders" dense>
          <template #avatar>
            <q-icon name="warning" color="negative" />
          </template>
          Por favor ajusta la ubicación de entrega para verificar la cobertura.
        </q-banner>

        <q-btn
          unelevated
          no-caps
          :color="metodoPago === 'efectivo' ? 'positive' : 'primary'"
          :label="metodoPago === 'efectivo' ? 'Confirmar Pedido (Efectivo)' : 'Confirmar y Pagar por QR'"
          :icon-right="metodoPago === 'efectivo' ? 'payments' : 'qr_code'"
          :loading="creandoPedido"
          :disable="!puedeCrearPedido"
          class="full-width text-weight-bold q-mt-md"
          :class="metodoPago === 'qr' ? 'btn-cta-gradient' : 'shadow-2'"
          style="min-height: 50px; font-size: 15px;"
          @click="confirmarPedido"
        />
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCarritoStore } from '../../shared/stores/carrito';
import { useAuthStore } from '../../shared/stores/auth';
import { api } from '../../shared/api/client';
import { Notify } from 'quasar';
import MapaPicker from '../../shared/components/MapaPicker.vue';
import type { Direccion } from '../../shared/types';

const router = useRouter();
const carritoStore = useCarritoStore();
const authStore = useAuthStore();

const tipoDireccion = ref<'guardada' | 'nueva'>('nueva');
const direccionesGuardadas = ref<Direccion[]>([]);
const direccionSeleccionadaId = ref<number | null>(null);

const lat = ref(-17.7833);
const lng = ref(-63.1821);
const direccionTexto = ref('');
const referencia = ref('');
const guardarDireccion = ref(false);
const alias = ref('Casa');
const costoEnvio = ref(8);
const zonaCubre = ref(true);
const notasCliente = ref('');
const metodoPago = ref<'qr' | 'efectivo'>('qr');
const aceptarEfectivo = ref(true);
const creandoPedido = ref(false);

const puedeCrearPedido = computed(() => {
  if (carritoStore.estaVacio) return false;
  if (!zonaCubre.value) return false;
  if (tipoDireccion.value === 'guardada') {
    return !!direccionSeleccionadaId.value;
  }
  return direccionTexto.value.trim().length > 3;
});

onMounted(async () => {
  if (carritoStore.estaVacio) {
    router.replace('/carrito');
    return;
  }

  try {
    const confResp = await api.get('/configuracion-pago');
    aceptarEfectivo.value = confResp.data?.aceptar_efectivo !== false;
  } catch (e) {}

  // Si está autenticado, cargar direcciones previas
  if (authStore.isAuthenticated) {
    try {
      const resp = await api.get('/direcciones');
      direccionesGuardadas.value = resp.data.data;
      if (direccionesGuardadas.value.length > 0) {
        tipoDireccion.value = 'guardada';
        const principal = direccionesGuardadas.value.find((d) => d.es_principal) || direccionesGuardadas.value[0];
        if (principal) {
          direccionSeleccionadaId.value = principal.id;
          if (principal.zona) {
            costoEnvio.value = parseFloat(String(principal.zona.costo_envio));
          }
        }
      }
    } catch (e) {
      console.error(e);
    }
  }
});

function onUbicacionCambiada(payload: { lat: number; lng: number; zona: any; costoEnvio: number; cubre: boolean }) {
  lat.value = payload.lat;
  lng.value = payload.lng;
  costoEnvio.value = payload.costoEnvio;
  zonaCubre.value = payload.cubre;
}

async function confirmarPedido() {
  if (!authStore.isAuthenticated) {
    Notify.create({
      type: 'warning',
      message: 'Inicia sesión para registrar tu pedido.',
    });
    router.push('/login');
    return;
  }

  creandoPedido.value = true;
  try {
    const payload: any = {
      items: carritoStore.items.map((i) => ({
        producto_id: i.producto.id,
        cantidad: i.cantidad,
      })),
      notas_cliente: notasCliente.value.trim() || undefined,
      metodo_pago: metodoPago.value,
    };

    if (tipoDireccion.value === 'guardada' && direccionSeleccionadaId.value) {
      payload.direccion_id = direccionSeleccionadaId.value;
    } else {
      payload.direccion_texto = direccionTexto.value.trim();
      payload.referencia = referencia.value.trim() || undefined;
      payload.lat = lat.value;
      payload.lng = lng.value;

      // Si marcó guardar dirección
      if (guardarDireccion.value) {
        try {
          await api.post('/direcciones', {
            alias: alias.value.trim() || 'Mi dirección',
            direccion: direccionTexto.value.trim(),
            referencia: referencia.value.trim() || undefined,
            lat: lat.value,
            lng: lng.value,
          });
        } catch (e) {
          // No detener el pedido si falla guardar la dirección
        }
      }
    }

    const resp = await api.post('/pedidos', payload);
    const pedidoCreado = resp.data.data;

    // Limpiar carrito
    carritoStore.limpiar();

    if (metodoPago.value === 'efectivo') {
      Notify.create({
        type: 'positive',
        message: '¡Pedido confirmado! Pagarás en efectivo al recibirlo.',
      });
      router.replace(`/pedido/${pedidoCreado.id}`);
    } else {
      Notify.create({
        type: 'positive',
        message: '¡Pedido creado con éxito! Realiza el pago por QR.',
      });
      router.replace(`/pedido/${pedidoCreado.id}/pago`);
    }
  } catch (err: any) {
    const msg = err.response?.data?.message || 'Error al crear el pedido. Intenta nuevamente.';
    Notify.create({
      type: 'negative',
      message: msg,
    });
  } finally {
    creandoPedido.value = false;
  }
}
</script>
