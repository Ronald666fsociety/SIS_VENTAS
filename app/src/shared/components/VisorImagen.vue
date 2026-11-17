<template>
  <q-dialog
    :model-value="modelValue"
    maximized
    transition-show="fade"
    transition-hide="fade"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <q-card class="column bg-black text-white no-scroll">
      <!-- Barra superior -->
      <q-toolbar class="bg-grey-10 text-white">
        <q-toolbar-title class="text-subtitle1 text-weight-bold ellipsis">
          {{ titulo || 'Vista completa' }}
        </q-toolbar-title>
        <span v-if="imagenes.length > 1" class="text-caption text-grey-4 q-mr-sm">
          {{ indiceActual + 1 }} / {{ imagenes.length }}
        </span>
        <q-btn flat round dense icon="close" @click="cerrar" />
      </q-toolbar>

      <!-- Imagen completa sin recortes -->
      <q-card-section class="col flex flex-center q-pa-sm bg-black relative-position overflow-hidden">
        <q-btn
          v-if="imagenes.length > 1"
          round
          flat
          color="white"
          icon="chevron_left"
          size="lg"
          class="absolute-left q-ml-xs z-top bg-dark-translucent"
          style="top: 50%; transform: translateY(-50%);"
          @click.stop="anterior"
        />

        <img
          v-if="urlActual"
          :src="urlActual"
          :alt="titulo || 'Imagen completa'"
          class="visor-img-completa"
        />

        <q-btn
          v-if="imagenes.length > 1"
          round
          flat
          color="white"
          icon="chevron_right"
          size="lg"
          class="absolute-right q-mr-xs z-top bg-dark-translucent"
          style="top: 50%; transform: translateY(-50%);"
          @click.stop="siguiente"
        />
      </q-card-section>

      <!-- Miniaturas inferiores si hay varias fotos o botón de acción opcional -->
      <q-card-section
        v-if="imagenes.length > 1 || accionLabel"
        class="bg-grey-10 q-pa-sm column q-gutter-y-sm"
      >
        <div v-if="imagenes.length > 1" class="row justify-center q-gutter-x-xs">
          <img
            v-for="(url, idx) in imagenes"
            :key="idx"
            :src="url"
            class="rounded-borders cursor-pointer"
            :style="{
              width: '48px',
              height: '48px',
              objectFit: 'cover',
              border: idx === indiceActual ? '2px solid #1976d2' : '2px solid transparent',
              opacity: idx === indiceActual ? 1 : 0.6,
            }"
            @click="indiceActual = idx"
          />
        </div>

        <q-btn
          v-if="accionLabel"
          unelevated
          color="primary"
          :label="accionLabel"
          icon="visibility"
          class="full-width text-weight-bold"
          @click="onAccion"
        />
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';

const props = defineProps<{
  modelValue: boolean;
  imagenes: string[];
  indiceInicial?: number | undefined;
  titulo?: string | undefined;
  accionLabel?: string | undefined;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void;
  (e: 'accion'): void;
}>();

const indiceActual = ref(0);

watch(
  () => props.modelValue,
  (abierto) => {
    if (abierto) {
      const idx = props.indiceInicial ?? 0;
      indiceActual.value = idx >= 0 && idx < props.imagenes.length ? idx : 0;
    }
  }
);

const urlActual = computed(() => props.imagenes[indiceActual.value] || props.imagenes[0] || '');

function cerrar() {
  emit('update:modelValue', false);
}

function anterior() {
  if (props.imagenes.length <= 1) return;
  indiceActual.value = (indiceActual.value - 1 + props.imagenes.length) % props.imagenes.length;
}

function siguiente() {
  if (props.imagenes.length <= 1) return;
  indiceActual.value = (indiceActual.value + 1) % props.imagenes.length;
}

function onAccion() {
  cerrar();
  emit('accion');
}
</script>

<style scoped>
.visor-img-completa {
  max-width: 100%;
  max-height: 100%;
  width: auto;
  height: auto;
  object-fit: contain;
  touch-action: pinch-zoom;
  user-select: none;
}
.bg-dark-translucent {
  background-color: rgba(0, 0, 0, 0.45);
}
</style>
