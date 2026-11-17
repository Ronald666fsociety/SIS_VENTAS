<template>
  <div v-if="authStore.isAuthenticated" class="inline-block">
    <q-btn flat round dense icon="notifications_none" @click="abrirPanel">
      <q-badge v-if="sinLeer > 0" color="negative" floating rounded class="tabular-nums">
        {{ sinLeer > 99 ? '99+' : sinLeer }}
      </q-badge>
      <q-tooltip>Notificaciones</q-tooltip>
    </q-btn>

    <q-dialog v-model="modalAbierto" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column" style="background: var(--surface-canvas);">
        <q-toolbar :class="APP_TARGET === 'negocio' ? 'header-negocio-glass' : 'header-cliente-glass'">
          <q-toolbar-title class="text-subtitle1 text-weight-bolder">
            Notificaciones ({{ sinLeer }} sin leer)
          </q-toolbar-title>
          <q-btn
            v-if="sinLeer > 0"
            flat
            dense
            no-caps
            icon="done_all"
            label="Marcar leídas"
            class="q-mr-xs"
            @click="marcarTodas"
          />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <div v-if="cargando" class="column items-center q-pa-xl">
            <q-spinner color="primary" size="40px" />
          </div>

          <div v-else-if="lista.length === 0" class="column items-center q-pa-xl text-center text-grey-6">
            <q-icon name="notifications_none" size="64px" />
            <div class="text-subtitle1 q-mt-sm">No tienes notificaciones aún</div>
          </div>

          <q-list v-else separator class="bg-white rounded-borders shadow-1">
            <q-item
              v-for="n in lista"
              :key="n.id"
              clickable
              v-ripple
              :class="!n.leida ? 'bg-blue-1' : ''"
              @click="abrirNotificacion(n)"
            >
              <q-item-section avatar>
                <q-avatar :color="!n.leida ? 'primary' : 'grey-4'" text-color="white" size="38px" icon="notifications" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold text-grey-9">{{ n.titulo }}</q-item-label>
                <q-item-label caption class="text-grey-8">{{ n.mensaje }}</q-item-label>
                <q-item-label caption class="text-grey-5">{{ formatearFecha(n.created_at) }}</q-item-label>
              </q-item-section>
              <q-item-section side v-if="!n.leida">
                <q-badge color="primary" rounded />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useRouter } from 'vue-router';
import { api, APP_TARGET } from '../api/client';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const modalAbierto = ref(false);
const cargando = ref(false);
const lista = ref<any[]>([]);
const sinLeer = ref(0);
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
  if (authStore.isAuthenticated) {
    cargar(true);
    timer = setInterval(() => cargar(true), 15000);
  }
});

onBeforeUnmount(() => {
  if (timer) {
    clearInterval(timer);
    timer = null;
  }
});

async function cargar(silencioso = false) {
  if (!authStore.isAuthenticated) return;
  if (!silencioso) cargando.value = true;
  try {
    const resp = await api.get('/notificaciones');
    lista.value = resp.data?.data || [];
    sinLeer.value = resp.data?.sin_leer || 0;
  } catch (e) {
  } finally {
    if (!silencioso) cargando.value = false;
  }
}

function abrirPanel() {
  modalAbierto.value = true;
  cargar(false);
}

async function marcarTodas() {
  try {
    await api.post('/notificaciones/leidas');
    lista.value.forEach((n) => (n.leida = true));
    sinLeer.value = 0;
  } catch (e) {}
}

async function abrirNotificacion(n: any) {
  if (!n.leida) {
    try {
      await api.put(`/notificaciones/${n.id}/leida`);
      n.leida = true;
      sinLeer.value = Math.max(0, sinLeer.value - 1);
    } catch (e) {}
  }

  modalAbierto.value = false;

  if (APP_TARGET === 'cliente') {
    if (n.pedido_id) {
      router.push(`/pedido/${n.pedido_id}`);
    }
  } else {
    if (authStore.esRepartidor) {
      router.push('/negocio/repartidor');
    } else if (String(n.titulo || '').toLowerCase().includes('comprobante')) {
      router.push('/negocio/pagos');
    } else {
      router.push('/negocio/pedidos');
    }
  }
}

function formatearFecha(f?: string): string {
  if (!f) return '';
  return new Date(f).toLocaleString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>
