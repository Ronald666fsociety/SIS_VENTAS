<template>
  <q-page class="q-pa-md column justify-between">
    <div v-if="carritoStore.estaVacio" class="column items-center justify-center col q-pa-xl text-center">
      <div class="surface-soft q-pa-lg rounded-xl q-mb-md">
        <q-icon name="shopping_bag" size="64px" color="indigo-3" />
      </div>
      <div class="text-h6 text-weight-bolder text-grey-9">Tu carrito está vacío</div>
      <div class="text-body2 text-grey-6 q-mt-xs q-mb-lg">Agrega productos del catálogo para armar tu pedido</div>
      <q-btn
        unelevated
        no-caps
        label="Explorar catálogo"
        icon="storefront"
        to="/inicio"
        class="btn-cta-gradient q-px-lg q-py-sm text-weight-bold"
      />
    </div>

    <!-- Lista de items en el carrito -->
    <div v-else class="col">
      <div class="row items-center justify-between q-mb-sm">
        <div class="text-subtitle1 text-weight-bolder text-grey-9">
          Productos seleccionados ({{ carritoStore.cantidadTotal }})
        </div>
        <q-btn
          flat
          dense
          no-caps
          color="negative"
          icon="delete_sweep"
          label="Vaciar carrito"
          size="sm"
          @click="vaciarCarrito"
        />
      </div>

      <q-list separator class="bg-white rounded-borders shadow-1 q-mb-md">
        <q-item
          v-for="item in carritoStore.items"
          :key="item.producto.id"
          class="q-py-md"
        >
          <!-- Imagen miniatura -->
          <q-item-section avatar>
            <q-avatar rounded size="60px" class="bg-grey-2">
              <img
                :src="item.producto.imagenes?.[0]?.url || 'https://placehold.co/100x100/eee/999?text=Item'"
                style="object-fit: cover;"
              />
            </q-avatar>
          </q-item-section>

          <!-- Info producto -->
          <q-item-section>
            <q-item-label class="text-weight-bold text-subtitle2">
              {{ item.producto.nombre }}
            </q-item-label>
            <q-item-label caption class="text-grey-7">
              Bs {{ formatearPrecio(item.producto.precio_final || item.producto.precio) }} c/u
            </q-item-label>
            <q-item-label class="text-primary text-weight-bolder q-mt-xs">
              Subtotal: Bs {{ calcularSubtotalItem(item) }}
            </q-item-label>
          </q-item-section>

          <!-- Control de cantidad (+ / -) y botón Quitar -->
          <q-item-section side>
            <div class="column items-end q-gutter-y-xs">
              <div class="row items-center no-wrap bg-grey-2 rounded-borders q-pa-xs">
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  :icon="item.cantidad > 1 ? 'remove' : 'delete_outline'"
                  :color="item.cantidad > 1 ? 'grey-8' : 'negative'"
                  @click="quitarUnidad(item.producto.id)"
                />
                <span class="text-weight-bolder text-subtitle2 q-px-sm">
                  {{ item.cantidad }}
                </span>
                <q-btn
                  flat
                  dense
                  round
                  size="sm"
                  icon="add"
                  color="primary"
                  :disable="item.cantidad >= item.producto.stock"
                  @click="agregarUnidad(item.producto)"
                />
              </div>

              <q-btn
                flat
                dense
                no-caps
                color="negative"
                icon="delete"
                label="Quitar"
                size="xs"
                @click="eliminarProducto(item.producto.id, item.producto.nombre)"
              />
            </div>
          </q-item-section>
        </q-item>
      </q-list>

      <div class="row justify-center q-mb-md">
        <q-btn
          outline
          no-caps
          color="primary"
          icon="add_shopping_cart"
          label="Seguir comprando / Agregar más productos"
          to="/inicio"
        />
      </div>
    </div>

    <!-- Panel de total y botón de checkout fijo abajo -->
    <q-card v-if="!carritoStore.estaVacio" flat bordered class="bg-white q-pa-md q-mt-auto">
      <div class="row justify-between items-center text-subtitle1 q-mb-xs">
        <span class="text-grey-7 text-weight-medium">Subtotal ({{ carritoStore.cantidadTotal }} items)</span>
        <span class="text-weight-bolder text-h6 text-primary tabular-nums font-display">
          Bs {{ carritoStore.subtotal.toFixed(2) }}
        </span>
      </div>
      <div class="text-caption text-grey-6 q-mb-md">
        * El costo de envío se calculará al confirmar tu dirección
      </div>

      <q-btn
        unelevated
        no-caps
        label="Elegir ubicación y continuar"
        icon-right="arrow_forward"
        to="/checkout"
        class="full-width btn-cta-gradient text-weight-bold"
        style="min-height: 48px; font-size: 15px;"
      />
    </q-card>
  </q-page>
</template>

<script setup lang="ts">
import { useCarritoStore, type ItemCarrito } from '../../shared/stores/carrito';
import { Notify } from 'quasar';
import type { Producto } from '../../shared/types';

const carritoStore = useCarritoStore();

function formatearPrecio(val: string | number): string {
  const n = typeof val === 'number' ? val : parseFloat(String(val));
  return isNaN(n) ? '0.00' : n.toFixed(2);
}

function calcularSubtotalItem(item: ItemCarrito): string {
  const p = parseFloat(String(item.producto.precio_final || item.producto.precio));
  return (p * item.cantidad).toFixed(2);
}

function agregarUnidad(prod: Producto) {
  carritoStore.agregar(prod, 1);
}

function quitarUnidad(productoId: number) {
  carritoStore.decrementar(productoId);
}

function eliminarProducto(productoId: number, nombre: string) {
  carritoStore.eliminar(productoId);
  Notify.create({
    type: 'info',
    message: `${nombre} quitado del carrito`,
    timeout: 1000,
    position: 'bottom',
  });
}

function vaciarCarrito() {
  carritoStore.limpiar();
  Notify.create({
    type: 'info',
    message: 'Carrito vaciado',
    timeout: 1000,
    position: 'bottom',
  });
}
</script>
