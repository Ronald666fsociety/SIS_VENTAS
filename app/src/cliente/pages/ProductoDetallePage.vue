<template>
  <q-page class="q-pa-md column justify-between">
    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando producto...</div>
    </div>

    <div v-else-if="producto" class="col">
      <!-- Carrusel de imágenes completas (click para ver en pantalla completa) -->
      <div class="q-mb-md rounded-borders overflow-hidden shadow-1 bg-white relative-position">
        <q-carousel
          v-if="producto.imagenes && producto.imagenes.length > 0"
          v-model="slideActual"
          swipeable
          animated
          arrows
          navigation
          infinite
          height="300px"
          class="bg-white"
        >
          <q-carousel-slide
            v-for="(img, idx) in producto.imagenes"
            :key="img.id"
            :name="idx"
            class="column no-wrap flex-center q-pa-xs cursor-pointer"
            @click="abrirVisor(idx)"
          >
            <img
              :src="img.url"
              :alt="producto.nombre"
              style="max-width: 100%; max-height: 100%; object-fit: contain;"
            />
          </q-carousel-slide>
        </q-carousel>
        <img
          v-else
          src="https://placehold.co/400x300/eee/999?text=Sin+Foto"
          style="width: 100%; height: 280px; object-fit: contain;"
          class="bg-grey-1"
        />

        <q-btn
          v-if="producto.imagenes && producto.imagenes.length > 0"
          round
          dense
          color="dark"
          icon="zoom_in"
          class="absolute-bottom-right q-ma-sm opacity-80"
          @click="abrirVisor(slideActual)"
        >
          <q-tooltip>Ver imagen completa</q-tooltip>
        </q-btn>
      </div>

      <!-- Datos del producto -->
      <q-card v-if="producto" flat bordered class="bg-white q-pa-md q-mb-md">
        <div class="row items-center justify-between">
          <q-badge color="indigo-1" text-color="primary" class="q-px-sm q-py-xs text-weight-bold text-uppercase" rounded>
            {{ producto.categoria?.nombre || 'General' }}
          </q-badge>
          <q-btn
            flat
            round
            dense
            :icon="favoritosStore.esFavorito(producto.id) ? 'favorite' : 'favorite_border'"
            :color="favoritosStore.esFavorito(producto.id) ? 'pink-6' : 'grey-6'"
            @click="favoritosStore.toggle(producto.id)"
          />
        </div>
        <div class="text-h5 text-weight-bolder text-grey-10 q-my-xs">
          {{ producto.nombre }}
        </div>

        <PrecioTag
          :precio="producto.precio"
          :precio-oferta="producto.precio_oferta"
          :precio-final="producto.precio_final"
          grande
          class="q-my-sm"
        />

        <div class="row items-center q-gutter-x-sm text-caption q-mt-xs">
          <q-badge
            :color="producto.stock > 0 ? 'positive' : 'negative'"
            :label="producto.stock > 0 ? `En stock (${producto.stock} disponibles)` : 'Agotado'"
          />
          <span class="text-grey-6">Unidad: {{ producto.unidad }}</span>
        </div>

        <q-separator class="q-my-md" />

        <div class="text-subtitle2 text-weight-bold q-mb-xs">Descripción</div>
        <div class="text-body2 text-grey-8" style="white-space: pre-line;">
          {{ producto.descripcion || 'Sin descripción detallada.' }}
        </div>
      </q-card>
    </div>

    <!-- Barra inferior fija con selector de cantidad y botón agregar -->
    <div v-if="producto && producto.stock > 0" class="bg-white rounded-borders shadow-2 q-pa-md q-mt-auto">
      <div class="row items-center justify-between q-mb-sm">
        <span class="text-subtitle1 text-weight-bold text-grey-8">Cantidad:</span>
        <div class="row items-center q-gutter-x-sm bg-grey-2 rounded-borders q-pa-xs">
          <q-btn
            round
            flat
            dense
            icon="remove"
            color="grey-8"
            :disable="cantidad <= 1"
            @click="cantidad--"
          />
          <span class="text-h6 text-weight-bolder q-px-sm">{{ cantidad }}</span>
          <q-btn
            round
            flat
            dense
            icon="add"
            color="primary"
            :disable="cantidad >= producto.stock"
            @click="cantidad++"
          />
        </div>
      </div>

      <q-btn
        unelevated
        no-caps
        icon="add_shopping_cart"
        :label="`Agregar al carrito · Bs ${(parseFloat(producto.precio_final) * cantidad).toFixed(2)}`"
        class="full-width btn-cta-gradient text-weight-bolder tabular-nums"
        style="min-height: 50px; font-size: 15px;"
        @click="agregar"
      />
    </div>

    <!-- Visor de imagen a pantalla completa -->
    <VisorImagen
      v-model="visorAbierto"
      :imagenes="urlsImagenes"
      :indice-inicial="visorIndice"
      :titulo="producto?.nombre"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../../shared/api/client';
import { useCarritoStore } from '../../shared/stores/carrito';
import { useFavoritosStore } from '../../shared/stores/favoritos';
import { Notify } from 'quasar';
import PrecioTag from '../../shared/components/PrecioTag.vue';
import VisorImagen from '../../shared/components/VisorImagen.vue';
import type { Producto } from '../../shared/types';

const route = useRoute();
const router = useRouter();
const carritoStore = useCarritoStore();
const favoritosStore = useFavoritosStore();

const productoId = route.params.id as string;
const producto = ref<Producto | null>(null);
const cargando = ref(true);
const slideActual = ref(0);
const cantidad = ref(1);

const visorAbierto = ref(false);
const visorIndice = ref(0);

const urlsImagenes = computed(() => {
  if (!producto.value?.imagenes) return [];
  return producto.value.imagenes.map((i) => i.url);
});

onMounted(async () => {
  try {
    const resp = await api.get(`/productos/${productoId}`);
    producto.value = resp.data.data;
  } catch (e: any) {
    Notify.create({
      type: 'negative',
      message: 'No se pudo cargar el producto',
    });
    router.replace('/inicio');
  } finally {
    cargando.value = false;
  }
});

function abrirVisor(idx: number) {
  if (urlsImagenes.value.length === 0) return;
  visorIndice.value = idx;
  visorAbierto.value = true;
}

function agregar() {
  if (!producto.value) return;
  carritoStore.agregar(producto.value, cantidad.value);
  Notify.create({
    type: 'positive',
    message: `${cantidad.value} x ${producto.value.nombre} agregado(s)`,
    position: 'bottom',
    actions: [
      {
        label: 'Ver Carrito',
        color: 'white',
        handler: () => router.push('/carrito'),
      },
    ],
  });
}
</script>

<style scoped>
.opacity-80 {
  opacity: 0.75;
}
</style>
