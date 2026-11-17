<template>
  <q-layout view="lHh Lpr lFf">
    <!-- Header moderno efecto cristal -->
    <q-header class="header-cliente-glass">
      <q-toolbar class="app-container q-py-xs">
        <q-btn
          v-if="mostrarAtras"
          flat
          round
          dense
          icon="arrow_back_ios_new"
          color="grey-9"
          class="q-mr-xs"
          @click="router.back()"
        />
        <div v-else class="brand-squircle-sm q-mr-sm">
          <q-icon name="shopping_bag" size="18px" />
        </div>

        <q-toolbar-title class="text-weight-bolder text-subtitle1 text-grey-10">
          {{ tituloPantalla }}
        </q-toolbar-title>

        <NotificacionesBell />

        <q-btn
          flat
          round
          dense
          icon="shopping_bag"
          color="grey-9"
          to="/carrito"
        >
          <q-badge v-if="carritoStore.cantidadTotal > 0" color="negative" floating rounded class="tabular-nums">
            {{ carritoStore.cantidadTotal }}
          </q-badge>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- Contenido de las páginas -->
    <q-page-container style="background: var(--surface-canvas);">
      <div class="app-container">
        <router-view />
      </div>
    </q-page-container>

    <!-- Barra de navegación inferior móvil -->
    <q-footer class="footer-glass text-grey-8">
      <q-tabs
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
          to="/inicio"
          exact
          icon="storefront"
          label="Inicio"
        />
        <q-route-tab
          name="carrito"
          to="/carrito"
          exact
          icon="shopping_cart"
          label="Carrito"
        >
          <q-badge v-if="carritoStore.cantidadTotal > 0" color="negative" floating rounded class="tabular-nums">
            {{ carritoStore.cantidadTotal }}
          </q-badge>
        </q-route-tab>
        <q-route-tab
          name="pedidos"
          to="/pedidos"
          exact
          icon="receipt_long"
          label="Pedidos"
        />
        <q-route-tab
          name="perfil"
          to="/perfil"
          exact
          icon="person_outline"
          label="Perfil"
        />
      </q-tabs>
    </q-footer>
  </q-layout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useCarritoStore } from '../../shared/stores/carrito';
import NotificacionesBell from '../../shared/components/NotificacionesBell.vue';

const route = useRoute();
const router = useRouter();
const carritoStore = useCarritoStore();
const tabActual = ref('inicio');

const mostrarAtras = computed(() => {
  return (
    route.path.includes('/producto/') ||
    route.path.includes('/checkout') ||
    route.path.includes('/pedido/')
  );
});

const tituloPantalla = computed(() => {
  if (route.path.includes('/carrito')) return 'Mi Carrito';
  if (route.path.includes('/pedidos')) return 'Mis Pedidos';
  if (route.path.includes('/perfil')) return 'Mi Cuenta';
  if (route.path.includes('/checkout')) return 'Confirmar Pedido';
  if (route.path.includes('/pago')) return 'Pago por QR';
  if (route.path.includes('/producto/')) return 'Detalle del Producto';
  if (route.path.includes('/pedido/')) return 'Seguimiento';
  return 'Tienda Móvil';
});
</script>

<style scoped>
.q-footer {
  border-top: 1px solid #e0e0e0;
}
</style>
