<template>
  <q-layout view="lHh Lpr lFf">
    <!-- Header administrativo moderno -->
    <q-header class="header-negocio-glass">
      <q-toolbar class="app-container q-py-xs">
        <div class="brand-squircle-sm q-mr-sm">
          <q-icon name="store" size="18px" />
        </div>
        <q-toolbar-title class="text-weight-bold text-subtitle1">
          {{ tituloPantalla }}
        </q-toolbar-title>

        <q-chip
          v-if="authStore.user"
          dense
          color="indigo-6"
          text-color="white"
          class="text-weight-bold text-uppercase text-caption q-mr-xs"
        >
          {{ authStore.user.rol }}
        </q-chip>

        <NotificacionesBell />

        <q-btn
          flat
          round
          dense
          icon="logout"
          @click="cerrarSesion"
        >
          <q-tooltip>Cerrar Sesión</q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- Contenedor de páginas -->
    <q-page-container style="background: var(--surface-canvas);">
      <div class="app-container">
        <router-view />
      </div>
    </q-page-container>

    <!-- Navegación inferior adaptada al ROL -->
    <q-footer class="footer-glass text-grey-8">
      <!-- Pestañas para REPARTIDOR -->
      <q-tabs
        v-if="authStore.esRepartidor"
        v-model="tabActual"
        no-caps
        active-color="primary"
        indicator-color="primary"
        class="text-grey-6 app-container"
        dense
        align="justify"
      >
        <q-route-tab
          name="entregas"
          to="/negocio/repartidor"
          exact
          icon="two_wheeler"
          label="Mis Entregas"
        />
        <q-route-tab
          name="historial"
          to="/negocio/repartidor/historial"
          exact
          icon="history"
          label="Historial"
        />
        <q-route-tab
          name="perfil"
          to="/negocio/perfil"
          exact
          icon="person_outline"
          label="Mi Perfil"
        />
      </q-tabs>

      <!-- Pestañas para ADMIN u OPERADOR -->
      <q-tabs
        v-else
        v-model="tabActual"
        no-caps
        active-color="primary"
        indicator-color="primary"
        class="text-grey-6 app-container"
        dense
        align="justify"
      >
        <q-route-tab
          name="inicio"
          to="/negocio/dashboard"
          exact
          icon="dashboard"
          label="Inicio"
        />
        <q-route-tab
          name="pagos"
          to="/negocio/pagos"
          exact
          icon="account_balance_wallet"
          label="Pagos"
        >
          <q-badge v-if="contadorPagosPendientes > 0" color="negative" floating rounded class="tabular-nums">
            {{ contadorPagosPendientes }}
          </q-badge>
        </q-route-tab>
        <q-route-tab
          name="pedidos"
          to="/negocio/pedidos"
          exact
          icon="receipt_long"
          label="Pedidos"
        />
        <q-route-tab
          v-if="authStore.esAdmin"
          name="catalogo"
          to="/negocio/catalogo"
          exact
          icon="inventory_2"
          label="Catálogo"
        />
        <q-route-tab
          name="mas"
          to="/negocio/mas"
          exact
          icon="menu"
          label="Más"
        />
      </q-tabs>
    </q-footer>
  </q-layout>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../../shared/stores/auth';
import { api } from '../../shared/api/client';
import NotificacionesBell from '../../shared/components/NotificacionesBell.vue';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const tabActual = ref('inicio');
const contadorPagosPendientes = ref(0);

const tituloPantalla = computed(() => {
  if (route.path.includes('/pagos')) return 'Bandeja de Pagos';
  if (route.path.includes('/pedidos')) return 'Gestión de Pedidos';
  if (route.path.includes('/catalogo')) return 'Catálogo y Stock';
  if (route.path.includes('/repartidor')) return 'Panel de Repartidor';
  if (route.path.includes('/mas')) return 'Opciones del Negocio';
  return 'Panel de Administración';
});

onMounted(() => {
  if (authStore.esPersonal && !authStore.esRepartidor) {
    actualizarContadorPagos();
    // Polling ligero cada 25 segundos con la app abierta (exigencia del diseño móvil)
    setInterval(actualizarContadorPagos, 25000);
  }
});

async function actualizarContadorPagos() {
  try {
    const resp = await api.get('/admin/dashboard');
    contadorPagosPendientes.value = resp.data.pagos_por_verificar || 0;
  } catch (e) {}
}

async function cerrarSesion() {
  await authStore.logout();
  router.replace('/login');
}
</script>

<style scoped>
.q-footer {
  border-top: 1px solid #e0e0e0;
}
</style>
