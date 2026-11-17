import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Producto, Direccion, ZonaEnvio } from '../types';

export interface ItemCarrito {
  producto: Producto;
  cantidad: number;
}

export const useCarritoStore = defineStore('carrito', () => {
  let initialItems: ItemCarrito[] = [];
  try {
    const raw = localStorage.getItem('tienda_carrito');
    if (raw && raw !== 'undefined' && raw !== 'null') {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        initialItems = parsed;
      }
    }
  } catch (e) {
    localStorage.removeItem('tienda_carrito');
  }

  const items = ref<ItemCarrito[]>(initialItems);

  const direccion = ref<Direccion | null>(null);
  const direccionTextoManual = ref('');
  const referenciaManual = ref('');
  const latManual = ref<number | null>(null);
  const lngManual = ref<number | null>(null);
  const zonaValidada = ref<ZonaEnvio | null>(null);
  const costoEnvio = ref<number>(0);
  const notasCliente = ref('');

  function persistir() {
    localStorage.setItem('tienda_carrito', JSON.stringify(items.value));
  }

  const cantidadTotal = computed(() => {
    return items.value.reduce((acc, i) => acc + i.cantidad, 0);
  });

  const subtotal = computed(() => {
    return items.value.reduce((acc, i) => {
      const precio = parseFloat(String(i.producto.precio_final || i.producto.precio));
      return acc + precio * i.cantidad;
    }, 0);
  });

  const total = computed(() => {
    return subtotal.value + costoEnvio.value;
  });

  const estaVacio = computed(() => items.value.length === 0);

  function agregar(producto: Producto, cantidad = 1) {
    const idx = items.value.findIndex((i) => i.producto.id === producto.id);
    const item = idx >= 0 ? items.value[idx] : undefined;
    if (item) {
      const nuevaCantidad = item.cantidad + cantidad;
      if (nuevaCantidad <= producto.stock) {
        item.cantidad = nuevaCantidad;
      }
    } else {
      items.value.push({
        producto,
        cantidad: Math.min(cantidad, producto.stock),
      });
    }
    persistir();
  }

  function decrementar(productoId: number) {
    const idx = items.value.findIndex((i) => i.producto.id === productoId);
    const item = idx >= 0 ? items.value[idx] : undefined;
    if (item && idx >= 0) {
      if (item.cantidad > 1) {
        item.cantidad--;
      } else {
        items.value.splice(idx, 1);
      }
      persistir();
    }
  }

  function eliminar(productoId: number) {
    items.value = items.value.filter((i) => i.producto.id !== productoId);
    persistir();
  }

  function limpiar() {
    items.value = [];
    persistir();
  }

  function cantidadDe(productoId: number): number {
    const item = items.value.find((i) => i.producto.id === productoId);
    return item ? item.cantidad : 0;
  }

  return {
    items,
    direccion,
    direccionTextoManual,
    referenciaManual,
    latManual,
    lngManual,
    zonaValidada,
    costoEnvio,
    notasCliente,
    cantidadTotal,
    subtotal,
    total,
    estaVacio,
    agregar,
    decrementar,
    eliminar,
    limpiar,
    cantidadDe,
  };
});
