<template>
  <q-page class="q-pa-md">
    <div v-if="!authStore.isAuthenticated" class="column items-center justify-center q-pa-xl text-center">
      <q-icon name="account_circle" size="80px" color="grey-4" />
      <div class="text-h6 text-grey-8 q-mt-md">Bienvenido a la Tienda</div>
      <div class="text-caption text-grey-6 q-mb-lg">Inicia sesión o regístrate para gestionar tus pedidos y direcciones</div>
      <div class="column q-gutter-y-sm full-width" style="max-width: 300px;">
        <q-btn
          unelevated
          color="primary"
          label="Iniciar Sesión"
          to="/login"
          class="text-weight-bold"
        />
        <q-btn
          outline
          color="primary"
          label="Crear Cuenta"
          to="/registro"
          class="text-weight-bold"
        />
      </div>
    </div>

    <div v-else>
      <!-- Cabecera de usuario -->
      <q-card flat bordered class="hero-banner q-mb-md q-pa-md">
        <div class="row items-center q-gutter-x-md">
          <q-avatar size="62px" color="white" text-color="primary" class="text-h5 text-weight-bolder shadow-2">
            {{ authStore.user?.nombre?.charAt(0) || 'U' }}
          </q-avatar>
          <div>
            <div class="text-h6 text-weight-bolder text-white">
              {{ authStore.user?.nombre }} {{ authStore.user?.apellido || '' }}
            </div>
            <div class="text-caption text-indigo-1 row items-center q-gutter-x-xs">
              <q-icon name="phone_iphone" size="14px" />
              <span class="tabular-nums">{{ authStore.user?.telefono }}</span>
            </div>
            <div v-if="authStore.user?.email" class="text-caption text-indigo-1 row items-center q-gutter-x-xs">
              <q-icon name="mail_outline" size="14px" />
              <span>{{ authStore.user?.email }}</span>
            </div>
          </div>
        </div>
      </q-card>

      <!-- Resumen rápido de gastos -->
      <div v-if="resumenGastos" class="row q-col-gutter-sm q-mb-md">
        <div class="col-6">
          <q-card flat bordered class="card-interactive bg-white cursor-pointer" @click="abrirGastosDialog = true">
            <q-card-section class="q-pa-md">
              <div class="section-eyebrow">Total Gastado</div>
              <div class="text-h6 text-weight-bolder text-primary tabular-nums q-mt-xs">
                Bs {{ resumenGastos.total_gastado }}
              </div>
            </q-card-section>
          </q-card>
        </div>
        <div class="col-6">
          <q-card flat bordered class="card-interactive bg-white cursor-pointer" @click="abrirGastosDialog = true">
            <q-card-section class="q-pa-md">
              <div class="section-eyebrow">Este Mes ({{ resumenGastos.pedidos_completados }} ped.)</div>
              <div class="text-h6 text-weight-bolder text-positive tabular-nums q-mt-xs">
                Bs {{ resumenGastos.total_mes }}
              </div>
            </q-card-section>
          </q-card>
        </div>
      </div>

      <!-- Opciones de perfil -->
      <q-card flat bordered class="rounded-borders bg-white shadow-1 q-mb-md">
        <q-list separator>
          <q-item clickable v-ripple @click="abrirGastosDialog = true">
            <q-item-section avatar>
              <q-icon name="account_balance_wallet" color="indigo-8" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">Historial de Gastos</q-item-label>
              <q-item-label caption>Resumen de compras y promedio por pedido</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>

          <q-item clickable v-ripple @click="abrirFavoritos">
            <q-item-section avatar>
              <q-icon name="favorite" color="pink-6" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">Mis Productos Favoritos</q-item-label>
              <q-item-label caption>{{ favoritosStore.ids.length }} producto(s) guardado(s)</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>

          <q-item clickable v-ripple @click="abrirDireccionesDialog = true">
            <q-item-section avatar>
              <q-icon name="place" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">Mis Direcciones de Entrega</q-item-label>
              <q-item-label caption>{{ direcciones.length }} direcciones guardadas</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>

          <q-item clickable v-ripple @click="abrirNotificacionesDialog = true">
            <q-item-section avatar>
              <q-icon name="notifications" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">Notificaciones</q-item-label>
              <q-item-label caption>Avisos de cambio de estado de tus pedidos</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-badge v-if="notificacionesSinLeer > 0" color="red" rounded>
                {{ notificacionesSinLeer }}
              </q-badge>
              <q-icon v-else name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>

          <q-item clickable v-ripple @click="abrirServidor = true">
            <q-item-section avatar>
              <q-icon name="dns" color="indigo-7" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium">Servidor de Datos (Internet / Wi-Fi)</q-item-label>
              <q-item-label caption>Cambiar IP local Wi-Fi o URL de túnel Internet</q-item-label>
            </q-item-section>
            <q-item-section side>
              <q-icon name="chevron_right" color="grey-6" />
            </q-item-section>
          </q-item>
        </q-list>
      </q-card>

      <!-- Acciones de cuenta -->
      <div class="column q-gutter-y-sm q-mt-md">
        <q-btn
          outline
          color="grey-8"
          icon="logout"
          label="Cerrar Sesión"
          class="full-width text-weight-bold"
          style="min-height: 44px;"
          @click="cerrarSesion"
        />

        <q-btn
          flat
          color="negative"
          icon="delete_forever"
          label="Eliminar mi cuenta"
          class="full-width text-caption"
          @click="confirmarEliminarCuenta"
        />
      </div>
    </div>

    <!-- Diálogo de direcciones -->
    <q-dialog v-model="abrirDireccionesDialog" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 column">
        <q-toolbar class="bg-primary text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Mis Direcciones</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col q-pa-md scroll">
          <div v-if="direcciones.length === 0" class="text-center q-pa-lg text-grey-6">
            Aún no tienes direcciones guardadas.
          </div>
          <q-list v-else separator class="bg-white rounded-borders shadow-1">
            <q-item v-for="d in direcciones" :key="d.id">
              <q-item-section avatar>
                <q-icon name="location_on" color="primary" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ d.alias }}</q-item-label>
                <q-item-label caption>{{ d.direccion }}</q-item-label>
                <q-item-label v-if="d.referencia" caption class="text-grey-7">Ref: {{ d.referencia }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn flat round dense icon="delete" color="negative" @click="eliminarDireccion(d.id)" />
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo de notificaciones -->
    <q-dialog v-model="abrirNotificacionesDialog" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 column">
        <q-toolbar class="bg-primary text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Notificaciones</q-toolbar-title>
          <q-btn flat round dense icon="done_all" @click="marcarTodasLeidas">
            <q-tooltip>Marcar todas como leídas</q-tooltip>
          </q-btn>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col q-pa-md scroll">
          <div v-if="notificaciones.length === 0" class="text-center q-pa-lg text-grey-6">
            No tienes notificaciones recientes.
          </div>
          <q-list v-else separator class="bg-white rounded-borders shadow-1">
            <q-item
              v-for="n in notificaciones"
              :key="n.id"
              :class="!n.leida ? 'bg-blue-1' : ''"
              clickable
              @click="marcarLeida(n)"
            >
              <q-item-section avatar>
                <q-icon name="notifications" :color="!n.leida ? 'primary' : 'grey-6'" />
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ n.titulo }}</q-item-label>
                <q-item-label caption>{{ n.mensaje }}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Historial de Gastos -->
    <q-dialog v-model="abrirGastosDialog" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 column">
        <q-toolbar class="bg-primary text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Historial de Gastos</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col q-pa-md scroll">
          <div v-if="resumenGastos" class="column q-gutter-y-md">
            <q-card flat bordered class="bg-white rounded-borders shadow-1 q-pa-md">
              <div class="row justify-between q-py-xs">
                <span class="text-grey-7">Total histórico gastado:</span>
                <span class="text-weight-bolder text-primary">Bs {{ resumenGastos.total_gastado }}</span>
              </div>
              <div class="row justify-between q-py-xs">
                <span class="text-grey-7">Gastado este mes:</span>
                <span class="text-weight-bold text-positive">Bs {{ resumenGastos.total_mes }}</span>
              </div>
              <div class="row justify-between q-py-xs">
                <span class="text-grey-7">Compras realizadas:</span>
                <span class="text-weight-bold">{{ resumenGastos.pedidos_completados }}</span>
              </div>
              <div class="row justify-between q-py-xs">
                <span class="text-grey-7">Gasto promedio por compra:</span>
                <span class="text-weight-bold">Bs {{ resumenGastos.gasto_promedio }}</span>
              </div>
            </q-card>

            <div class="text-subtitle2 text-weight-bold text-grey-8">Últimas compras</div>
            <q-list v-if="resumenGastos.ultimos_pedidos?.length" separator class="bg-white rounded-borders shadow-1">
              <q-item
                v-for="g in resumenGastos.ultimos_pedidos"
                :key="g.id"
                clickable
                v-ripple
                @click="abrirGastosDialog = false; router.push(`/pedido/${g.id}`)"
              >
                <q-item-section>
                  <q-item-label class="text-weight-bold">#{{ g.codigo }}</q-item-label>
                  <q-item-label caption>
                    {{ g.estado_etiqueta }} · {{ String(g.metodo_pago || 'qr').toUpperCase() }}
                  </q-item-label>
                </q-item-section>
                <q-item-section side>
                  <div class="text-subtitle2 text-weight-bolder text-primary">Bs {{ g.total }}</div>
                  <q-rating v-if="g.calificacion" :model-value="g.calificacion" size="14px" color="amber-8" readonly />
                </q-item-section>
              </q-item>
            </q-list>
            <div v-else class="text-center text-grey-6 q-pa-lg">
              Aún no tienes compras completadas.
            </div>
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo: Mis Favoritos -->
    <q-dialog v-model="abrirFavoritosDialog" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="bg-grey-1 column">
        <q-toolbar class="bg-primary text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">Mis Favoritos</q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col q-pa-md scroll">
          <div v-if="productosFavoritos.length === 0" class="text-center q-pa-xl text-grey-6">
            <q-icon name="favorite_border" size="64px" color="grey-4" />
            <div class="q-mt-sm">Aún no marcaste productos como favoritos.</div>
          </div>
          <q-list v-else separator class="bg-white rounded-borders shadow-1">
            <q-item
              v-for="prod in productosFavoritos"
              :key="prod.id"
              clickable
              v-ripple
              @click="abrirFavoritosDialog = false; router.push(`/producto/${prod.id}`)"
            >
              <q-item-section avatar>
                <q-avatar rounded size="50px" class="bg-grey-2">
                  <img :src="prod.imagenes?.[0]?.url || 'https://placehold.co/100x100/eee/999?text=Prod'" style="object-fit: cover;" />
                </q-avatar>
              </q-item-section>
              <q-item-section>
                <q-item-label class="text-weight-bold">{{ prod.nombre }}</q-item-label>
                <q-item-label caption class="text-primary text-weight-bold">Bs {{ prod.precio_final }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-btn
                  flat
                  round
                  dense
                  icon="favorite"
                  color="pink-6"
                  @click.stop="quitarFavorito(prod.id)"
                />
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
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../shared/stores/auth';
import { useFavoritosStore } from '../../shared/stores/favoritos';
import { api } from '../../shared/api/client';
import { Notify, Dialog } from 'quasar';
import ServerConfigDialog from '../../shared/components/ServerConfigDialog.vue';
import type { Direccion, Producto } from '../../shared/types';

const router = useRouter();
const authStore = useAuthStore();
const favoritosStore = useFavoritosStore();

const direcciones = ref<Direccion[]>([]);
const notificaciones = ref<any[]>([]);
const notificacionesSinLeer = ref(0);
const abrirDireccionesDialog = ref(false);
const abrirNotificacionesDialog = ref(false);
const abrirServidor = ref(false);

const resumenGastos = ref<any | null>(null);
const abrirGastosDialog = ref(false);
const abrirFavoritosDialog = ref(false);
const productosFavoritos = ref<Producto[]>([]);

onMounted(() => {
  if (authStore.isAuthenticated) {
    cargarDirecciones();
    cargarNotificaciones();
    cargarGastos();
    favoritosStore.cargar();
  }
});

async function cargarGastos() {
  try {
    const resp = await api.get('/perfil/gastos');
    resumenGastos.value = resp.data;
  } catch (e) {}
}

async function abrirFavoritos() {
  abrirFavoritosDialog.value = true;
  try {
    const resp = await api.get('/favoritos');
    productosFavoritos.value = resp.data?.data || [];
  } catch (e) {}
}

async function quitarFavorito(id: number) {
  await favoritosStore.toggle(id);
  productosFavoritos.value = productosFavoritos.value.filter((p) => p.id !== id);
}

async function cargarDirecciones() {
  try {
    const resp = await api.get('/direcciones');
    direcciones.value = resp.data.data;
  } catch (e) {
    console.error(e);
  }
}

async function cargarNotificaciones() {
  try {
    const resp = await api.get('/notificaciones');
    notificaciones.value = resp.data.data || [];
    notificacionesSinLeer.value = resp.data.sin_leer || 0;
  } catch (e) {
    console.error(e);
  }
}

async function eliminarDireccion(id: number) {
  try {
    await api.delete(`/direcciones/${id}`);
    direcciones.value = direcciones.value.filter((d) => d.id !== id);
    Notify.create({ type: 'positive', message: 'Dirección eliminada' });
  } catch (e) {
    Notify.create({ type: 'negative', message: 'No se pudo eliminar' });
  }
}

async function marcarLeida(n: any) {
  if (!n.leida) {
    try {
      await api.put(`/notificaciones/${n.id}/leida`);
      n.leida = true;
      notificacionesSinLeer.value = Math.max(0, notificacionesSinLeer.value - 1);
    } catch (e) {}
  }
}

async function marcarTodasLeidas() {
  try {
    await api.post('/notificaciones/leidas');
    notificaciones.value.forEach((n) => (n.leida = true));
    notificacionesSinLeer.value = 0;
    Notify.create({ type: 'positive', message: 'Notificaciones leídas' });
  } catch (e) {}
}

async function cerrarSesion() {
  await authStore.logout();
  router.replace('/inicio');
}

function confirmarEliminarCuenta() {
  Dialog.create({
    title: 'Eliminar cuenta',
    message: 'Esta acción es permanente y exigida por Apple/Google. Ingresa tu contraseña para confirmar:',
    prompt: {
      model: '',
      type: 'password',
      isValid: (val) => val.length >= 6,
    },
    ok: {
      label: 'Eliminar definitivamente',
      color: 'negative',
    },
    cancel: true,
  }).onOk(async (password: string) => {
    try {
      await api.delete('/perfil', { data: { password } });
      authStore.limpiarSesion();
      Notify.create({
        type: 'info',
        message: 'Tu cuenta ha sido eliminada.',
      });
      router.replace('/inicio');
    } catch (err: any) {
      Notify.create({
        type: 'negative',
        message: err.response?.data?.message || 'Error al eliminar la cuenta',
      });
    }
  });
}
</script>
