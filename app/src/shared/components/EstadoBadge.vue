<template>
  <q-badge
    :color="colorConfig.color"
    :text-color="colorConfig.textColor"
    :label="etiqueta || colorConfig.label"
    class="q-px-sm q-py-xs text-weight-bold"
    style="border-radius: 999px; font-size: 11px; letter-spacing: 0.01em;"
    rounded
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { EstadoPedido, EstadoPago } from '../types';

const props = defineProps<{
  estado: EstadoPedido | EstadoPago | string;
  etiqueta?: string;
}>();

const colorConfig = computed(() => {
  switch (props.estado) {
    case 'pendiente_pago':
    case 'pendiente':
      return { color: 'amber-8', textColor: 'white', label: 'Pendiente de pago' };
    case 'pago_en_revision':
    case 'en_revision':
      return { color: 'primary', textColor: 'white', label: 'En revisión' };
    case 'pago_verificado':
    case 'verificado':
      return { color: 'positive', textColor: 'white', label: 'Pago verificado' };
    case 'preparando':
      return { color: 'cyan-8', textColor: 'white', label: 'Preparando' };
    case 'listo_para_envio':
      return { color: 'indigo-7', textColor: 'white', label: 'Listo para envío' };
    case 'en_camino':
      return { color: 'teal-7', textColor: 'white', label: 'En camino' };
    case 'entregado':
      return { color: 'positive', textColor: 'white', label: 'Entregado' };
    case 'rechazado':
      return { color: 'negative', textColor: 'white', label: 'Pago rechazado' };
    case 'cancelado':
      return { color: 'grey-8', textColor: 'white', label: 'Cancelado' };
    case 'entrega_fallida':
      return { color: 'deep-orange-9', textColor: 'white', label: 'Entrega fallida' };
    default:
      return { color: 'grey-7', textColor: 'white', label: props.estado };
  }
});
</script>
