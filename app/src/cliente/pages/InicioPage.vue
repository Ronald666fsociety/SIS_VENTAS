<template>
  <q-page class="q-pa-md">
    <!-- Hero sutil de bienvenida -->
    <div class="hero-banner q-pa-md q-mb-sm row items-center justify-between">
      <div>
        <div class="text-caption text-indigo-1 text-uppercase text-weight-bold" style="letter-spacing: 0.06em;">
          Envíos a toda Bolivia
        </div>
        <div class="text-h6 text-weight-bolder q-mt-xs">
          Explora nuestro catálogo
        </div>
        <div class="text-caption text-indigo-1">
          Paga con QR o efectivo contra entrega
        </div>
      </div>
      <div class="q-pa-sm rounded-borders" style="background: rgba(255, 255, 255, 0.16);">
        <q-icon name="local_mall" size="30px" color="white" />
      </div>
    </div>

    <UbicacionActualChip />

    <!-- Buscador -->
    <q-input
      v-model="catalogoStore.busqueda"
      outlined
      dense
      rounded
      bg-color="white"
      placeholder="¿Qué producto estás buscando hoy?"
      class="q-mb-md"
      debounce="300"
      @update:model-value="catalogoStore.cargarProductos"
      clearable
    >
      <template #prepend>
        <q-icon name="search" color="primary" />
      </template>
    </q-input>

    <!-- Chips horizontales de categorías -->
    <div class="row no-wrap scroll q-gutter-x-xs q-mb-sm no-scrollbar">
      <q-chip
        clickable
        :selected="catalogoStore.categoriaActiva === null"
        color="primary"
        :text-color="catalogoStore.categoriaActiva === null ? 'white' : 'primary'"
        :outline="catalogoStore.categoriaActiva !== null"
        label="Todos"
        icon="apps"
        class="text-weight-bold"
        @click="catalogoStore.seleccionarCategoria(null)"
      />
      <q-chip
        v-for="cat in catalogoStore.categorias"
        :key="cat.id"
        clickable
        :selected="catalogoStore.categoriaActiva === cat.id"
        color="primary"
        :text-color="catalogoStore.categoriaActiva === cat.id ? 'white' : 'primary'"
        :outline="catalogoStore.categoriaActiva !== cat.id"
        :label="cat.nombre"
        class="text-weight-medium"
        @click="catalogoStore.seleccionarCategoria(cat.id)"
      />
    </div>

    <!-- Filtros rápidos -->
    <div class="row q-gutter-x-sm q-mb-md">
      <q-btn
        rounded
        no-caps
        dense
        size="sm"
        :outline="!catalogoStore.soloOfertas"
        :unelevated="catalogoStore.soloOfertas"
        color="red-6"
        icon="local_offer"
        label="Ofertas"
        class="q-px-sm"
        @click="toggleOfertas"
      />
      <q-btn
        rounded
        no-caps
        dense
        size="sm"
        :outline="!catalogoStore.soloDestacados"
        :unelevated="catalogoStore.soloDestacados"
        color="amber-9"
        icon="star"
        label="Destacados"
        class="q-px-sm"
        @click="toggleDestacados"
      />
      <q-btn
        rounded
        no-caps
        dense
        size="sm"
        :outline="!soloFavoritos"
        :unelevated="soloFavoritos"
        color="pink-6"
        icon="favorite"
        :label="`Favoritos (${favoritosStore.ids.length})`"
        class="q-px-sm tabular-nums"
        @click="soloFavoritos = !soloFavoritos"
      />
    </div>

    <!-- Skeletons de carga -->
    <div v-if="catalogoStore.cargando" class="row q-col-gutter-md">
      <div v-for="n in 4" :key="n" class="col-6 col-sm-4">
        <q-card flat bordered class="rounded-borders">
          <q-skeleton height="150px" square />
          <q-card-section>
            <q-skeleton type="text" width="70%" />
            <q-skeleton type="text" width="40%" />
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Grilla de productos -->
    <div v-else-if="productosFiltrados.length > 0" class="row q-col-gutter-sm q-col-gutter-sm-md">
      <q-card
        v-for="p in productosFiltrados"
        :key="p.id"
        flat
        bordered
        class="col-6 col-sm-4 producto-card card-interactive rounded-borders column full-height cursor-pointer overflow-hidden bg-white"
        @click="verDetalle(p.id)"
      >
        <!-- Imagen del producto (click para ver completa) -->
        <div
          class="relative-position flex flex-center q-pa-sm"
          style="height: 168px; background: #f8fafc;"
          @click.stop="verImagenCompleta(p)"
        >
          <img
            :src="p.imagenes?.[0]?.url || 'https://placehold.co/400x400/eee/999?text=Sin+Foto'"
            style="max-width: 100%; max-height: 100%; object-fit: contain;"
          />

          <q-btn
            round
            flat
            dense
            size="sm"
            :icon="favoritosStore.esFavorito(p.id) ? 'favorite' : 'favorite_border'"
            :color="favoritosStore.esFavorito(p.id) ? 'pink-6' : 'grey-6'"
            class="absolute-top-left q-ma-xs bg-white shadow-1"
            @click.stop="favoritosStore.toggle(p.id)"
          />

          <q-btn
            v-if="p.imagenes && p.imagenes.length > 0"
            round
            dense
            size="xs"
            color="dark"
            icon="zoom_in"
            class="absolute-bottom-right q-ma-xs"
            style="opacity: 0.72;"
            @click.stop="verImagenCompleta(p)"
          />

          <q-badge
            v-if="p.stock <= 0"
            color="negative"
            label="Agotado"
            class="absolute-top-right q-ma-xs"
            rounded
          />
        </div>

        <!-- Información y botón -->
        <q-card-section class="q-pa-sm column col justify-between">
          <div>
            <div class="section-eyebrow ellipsis">
              {{ p.categoria?.nombre || 'General' }}
            </div>
            <div class="text-subtitle2 text-weight-bold text-grey-10 multiline-ellipsis q-mt-xs" style="line-height: 1.25;">
              {{ p.nombre }}
            </div>
          </div>

          <div class="q-mt-sm">
            <PrecioTag
              :precio="p.precio"
              :precio-oferta="p.precio_oferta"
              :precio-final="p.precio_final"
            />

            <!-- Controles de carrito (+ / - / Quitar) -->
            <div class="row items-center justify-end q-mt-xs">
              <template v-if="p.stock > 0">
                <div
                  v-if="cantidadEnCarrito(p.id) > 0"
                  class="row items-center no-wrap bg-indigo-1 rounded-borders q-pa-xs full-width justify-between"
                  @click.stop
                >
                  <q-btn
                    flat
                    dense
                    round
                    size="xs"
                    :icon="cantidadEnCarrito(p.id) > 1 ? 'remove' : 'delete'"
                    :color="cantidadEnCarrito(p.id) > 1 ? 'grey-8' : 'negative'"
                    @click.stop="quitarDelCarrito(p.id)"
                  />
                  <span class="text-weight-bolder text-body2 text-primary tabular-nums">
                    {{ cantidadEnCarrito(p.id) }}
                  </span>
                  <q-btn
                    flat
                    dense
                    round
                    size="xs"
                    icon="add"
                    color="primary"
                    :disable="cantidadEnCarrito(p.id) >= p.stock"
                    @click.stop="agregarAlCarrito(p)"
                  />
                </div>
                <q-btn
                  v-else
                  unelevated
                  dense
                  no-caps
                  color="primary"
                  icon="add_shopping_cart"
                  label="Agregar"
                  size="sm"
                  class="full-width text-weight-bold"
                  style="min-height: 34px;"
                  @click.stop="agregarAlCarrito(p)"
                />
              </template>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Estado vacío -->
    <div v-else class="column items-center q-pa-xl text-center">
      <q-icon name="search_off" size="64px" color="grey-5" />
      <div class="text-h6 text-grey-7 q-mt-md">No se encontraron productos</div>
      <div class="text-caption text-grey-6 q-mb-md">Prueba cambiando los filtros o la búsqueda</div>
      <q-btn
        flat
        color="primary"
        label="Ver todo el catálogo"
        @click="limpiarFiltros"
      />
    </div>

    <!-- Barra flotante de resumen de carrito si hay productos -->
    <div v-if="!carritoStore.estaVacio" class="q-mt-md">
      <q-banner rounded class="bg-primary text-white shadow-3">
        <div class="row items-center justify-between">
          <div>
            <div class="text-subtitle2 text-weight-bolder">
              {{ carritoStore.cantidadTotal }} producto(s) en tu carrito
            </div>
            <div class="text-caption">
              Subtotal: Bs {{ carritoStore.subtotal.toFixed(2) }}
            </div>
          </div>
          <q-btn
            unelevated
            color="white"
            text-color="primary"
            label="Ver Carrito"
            icon-right="shopping_cart"
            to="/carrito"
            class="text-weight-bolder"
          />
        </div>
      </q-banner>
    </div>

    <!-- Visor de imagen completa -->
    <VisorImagen
      v-model="visorAbierto"
      :imagenes="visorImagenes"
      :titulo="visorProducto?.nombre"
      accion-label="Ver detalles y comprar"
      @accion="visorProducto && verDetalle(visorProducto.id)"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { storeToRefs } from 'pinia';
import { useCatalogoStore } from '../../shared/stores/catalogo';
import { useCarritoStore } from '../../shared/stores/carrito';
import { useFavoritosStore } from '../../shared/stores/favoritos';
import { Notify } from 'quasar';
import PrecioTag from '../../shared/components/PrecioTag.vue';
import VisorImagen from '../../shared/components/VisorImagen.vue';
import UbicacionActualChip from '../../shared/components/UbicacionActualChip.vue';
import type { Producto } from '../../shared/types';

const router = useRouter();
const catalogoStore = useCatalogoStore();
const carritoStore = useCarritoStore();
const favoritosStore = useFavoritosStore();
const { productos } = storeToRefs(catalogoStore);

const soloFavoritos = ref(false);
const productosFiltrados = computed(() => {
  if (!soloFavoritos.value) return productos.value;
  return productos.value.filter((p) => favoritosStore.esFavorito(p.id));
});

const visorAbierto = ref(false);
const visorImagenes = ref<string[]>([]);
const visorProducto = ref<Producto | null>(null);

onMounted(() => {
  catalogoStore.cargarCategorias();
  catalogoStore.cargarProductos();
  favoritosStore.cargar();
});

function toggleOfertas() {
  catalogoStore.soloOfertas = !catalogoStore.soloOfertas;
  catalogoStore.cargarProductos();
}

function toggleDestacados() {
  catalogoStore.soloDestacados = !catalogoStore.soloDestacados;
  catalogoStore.cargarProductos();
}

function limpiarFiltros() {
  catalogoStore.busqueda = '';
  catalogoStore.categoriaActiva = null;
  catalogoStore.soloOfertas = false;
  catalogoStore.soloDestacados = false;
  soloFavoritos.value = false;
  catalogoStore.cargarProductos();
}

function verDetalle(id: number) {
  router.push(`/producto/${id}`);
}

function verImagenCompleta(prod: Producto) {
  if (!prod.imagenes || prod.imagenes.length === 0) {
    verDetalle(prod.id);
    return;
  }
  visorProducto.value = prod;
  visorImagenes.value = prod.imagenes.map((i) => i.url);
  visorAbierto.value = true;
}

function cantidadEnCarrito(productoId: number): number {
  const item = carritoStore.items.find((i) => i.producto.id === productoId);
  return item ? item.cantidad : 0;
}

function agregarAlCarrito(prod: Producto) {
  carritoStore.agregar(prod, 1);
  Notify.create({
    type: 'positive',
    message: `${prod.nombre} agregado al carrito`,
    timeout: 900,
    position: 'bottom',
  });
}

function quitarDelCarrito(productoId: number) {
  carritoStore.decrementar(productoId);
}
</script>

<style scoped>
.producto-card {
  transition: transform 0.15s ease-in-out;
}
.producto-card:active {
  transform: scale(0.98);
}
.multiline-ellipsis {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: 1.2;
  min-height: 2.4em;
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
