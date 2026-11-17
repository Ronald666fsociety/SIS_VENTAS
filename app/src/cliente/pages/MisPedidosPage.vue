<template>
  <q-page class="q-pa-md">
    <q-pull-to-refresh @refresh="refrescar">
      <div v-if="cargando" class="column items-center q-pa-xl">
        <q-spinner color="primary" size="48px" />
        <div class="text-caption text-grey-7 q-mt-md">Cargando tus pedidos...</div>
      </div>

      <!-- No autenticado -->
      <div v-else-if="!authStore.isAuthenticated" class="column items-center justify-center q-pa-xl text-center">
        <q-icon name="receipt_long" size="80px" color="grey-4" />
        <div class="text-h6 text-grey-8 q-mt-md">Inicia sesión para ver tus pedidos</div>
        <div class="text-caption text-grey-6 q-mb-lg">Podrás consultar el estado en tiempo real y tu historial</div>
        <q-btn
          unelevated
          color="primary"
          label="Iniciar Sesión"
          to="/login"
          class="q-px-lg text-weight-bold"
        />
      </div>

      <!-- Estado vacío -->
      <div v-else-if="pedidos.length === 0" class="column items-center justify-center q-pa-xl text-center">
        <q-icon name="receipt" size="80px" color="grey-4" />
        <div class="text-h6 text-grey-8 q-mt-md">Aún no tienes pedidos</div>
        <div class="text-caption text-grey-6 q-mb-lg">Descubre nuestros productos y haz tu primer pedido</div>
        <q-btn
          unelevated
          color="primary"
          label="Ver productos"
          icon="storefront"
          to="/inicio"
          class="q-px-lg q-py-sm text-weight-bold"
        />
      </div>

      <!-- Lista de pedidos -->
      <div v-else class="column q-gutter-y-md">
        <q-card
          v-for="p in pedidos"
          :key="p.id"
          flat
          bordered
          class="card-interactive bg-white cursor-pointer"
          @click="verSeguimiento(p.id)"
        >
          <q-card-section>
            <div class="row justify-between items-center q-mb-xs">
              <span class="text-subtitle1 text-weight-bolder text-grey-10 font-display tabular-nums">
                #{{ p.codigo }}
              </span>
              <EstadoBadge :estado="p.estado" :etiqueta="p.estado_etiqueta" />
            </div>

            <div class="text-caption text-grey-6">
              {{ formatearFecha(p.created_at) }}
            </div>

            <div class="row justify-between items-center q-mt-sm">
              <span class="text-caption text-grey-7 text-weight-medium">
                {{ p.items?.length || 0 }} productos
              </span>
              <span class="text-subtitle1 text-weight-bolder text-primary tabular-nums font-display">
                Bs {{ p.total }}
              </span>
            </div>
          </q-card-section>

          <q-separator />

          <q-card-actions align="between" class="q-px-md">
            <q-btn
              flat
              dense
              no-caps
              color="primary"
              label="Ver seguimiento"
              icon="visibility"
              :to="`/pedido/${p.id}`"
            />
            <q-btn
              flat
              dense
              no-caps
              color="grey-8"
              label="Repetir pedido"
              icon="replay"
              @click.stop="repetirPedido(p)"
            />
          </q-card-actions>
        </q-card>
      </div>
    </q-pull-to-refresh>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../../shared/api/client';
import { useCarritoStore } from '../../shared/stores/carrito';
import { useAuthStore } from '../../shared/stores/auth';
import { Notify } from 'quasar';
import EstadoBadge from '../../shared/components/EstadoBadge.vue';
import type { Pedido } from '../../shared/types';

const router = useRouter();
const authStore = useAuthStore();
const carritoStore = useCarritoStore();
const pedidos = ref<Pedido[]>([]);
const cargando = ref(true);

onMounted(() => {
  cargarPedidos();
});

async function cargarPedidos() {
  if (!authStore.isAuthenticated) {
    cargando.value = false;
    pedidos.value = [];
    return;
  }
  cargando.value = true;
  try {
    const resp = await api.get('/pedidos');
    pedidos.value = resp.data.data;
  } catch (e: any) {
    Notify.create({
      type: 'negative',
      message: 'Error al cargar tus pedidos',
    });
  } finally {
    cargando.value = false;
  }
}

async function refrescar(done: () => void) {
  await cargarPedidos();
  done();
}

function verSeguimiento(id: number) {
  router.push(`/pedido/${id}`);
}

async function repetirPedido(p: Pedido) {
  try {
    // Cargar detalle completo si no tiene items cargados
    const resp = await api.get(`/pedidos/${p.id}`);
    const detalle: Pedido = resp.data.data;

    if (detalle.items && detalle.items.length > 0) {
      for (const it of detalle.items) {
        // Mock rápido de producto con precio y stock suficiente
        carritoStore.agregar({
          id: it.producto_id,
          categoria_id: 1,
          nombre: it.nombre_producto,
          precio: it.precio_unitario,
          precio_final: String(it.precio_unitario),
          unidad: 'unidad',
          stock: 999,
        }, it.cantidad);
      }
      Notify.create({
        type: 'positive',
        message: 'Productos agregados al carrito',
      });
      router.push('/carrito');
    }
  } catch (e) {
    Notify.create({
      type: 'negative',
      message: 'No se pudo repetir el pedido',
    });
  }
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
</script>
