<template>
  <div class="row items-baseline q-gutter-xs tabular-nums">
    <span class="text-weight-bolder text-primary font-display" :class="grande ? 'text-h5' : 'text-subtitle1'">
      Bs {{ formatear(precioFinal) }}
    </span>
    <span
      v-if="tieneOferta"
      class="text-strike text-grey-6"
      :class="grande ? 'text-subtitle1' : 'text-caption'"
    >
      Bs {{ formatear(precio) }}
    </span>
    <q-badge
      v-if="tieneOferta"
      color="red-1"
      text-color="negative"
      label="OFERTA"
      class="q-ml-xs text-weight-bold q-px-xs"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    precio: number | string;
    precioOferta?: number | string | null | undefined;
    precioFinal?: number | string | undefined;
    grande?: boolean | undefined;
  }>(),
  {
    precioOferta: null,
    grande: false,
  }
);

const tieneOferta = computed(() => {
  if (!props.precioOferta) return false;
  return parseFloat(String(props.precioOferta)) < parseFloat(String(props.precio));
});

const precioFinal = computed(() => {
  if (props.precioFinal) return props.precioFinal;
  return tieneOferta.value ? props.precioOferta! : props.precio;
});

function formatear(val: number | string): string {
  const n = typeof val === 'number' ? val : parseFloat(val);
  return isNaN(n) ? '0.00' : n.toFixed(2);
}
</script>
