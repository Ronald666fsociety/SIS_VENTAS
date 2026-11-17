<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <q-card class="rounded-xl q-pa-lg" style="min-width: 330px; max-width: 92vw;">
      <div class="row items-center justify-between q-mb-md">
        <div class="row items-center q-gutter-x-sm">
          <div class="brand-squircle-sm">
            <q-icon name="dns" size="18px" />
          </div>
          <div>
            <div class="text-subtitle1 text-weight-bolder text-grey-10">Servidor de Datos</div>
            <div class="text-caption text-grey-6">Conexión con tu base de datos Laravel</div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-7" v-close-popup />
      </div>

      <div class="text-caption text-grey-7 q-mb-sm">
        Dirección actual: <b class="text-primary">{{ urlActual }}</b>
      </div>

      <q-input
        v-model="urlInput"
        outlined
        dense
        label="URL del servidor API"
        placeholder="http://192.168.10.127:8000"
        hint="Usa la URL de Internet (https://...) o la IP Wi-Fi local de tu PC"
        class="q-mb-sm"
      >
        <template #prepend>
          <q-icon name="link" color="primary" />
        </template>
      </q-input>

      <div class="row q-gutter-xs q-mb-md">
        <q-btn
          dense
          outline
          no-caps
          size="sm"
          color="primary"
          icon="wifi"
          label="Wi-Fi Local (192.168.10.127)"
          @click="urlInput = 'http://192.168.10.127:8000/api/v1'"
        />
      </div>

      <div class="row q-gutter-x-sm">
        <q-btn
          outline
          no-caps
          color="grey-7"
          label="Restaurar"
          class="col-4"
          @click="restaurar"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="wifi_find"
          label="Probar y Guardar"
          :loading="probando"
          class="col text-weight-bold"
          @click="guardarYProbar"
        />
      </div>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import axios from 'axios';
import { Notify } from 'quasar';
import { api, resolveApiBase, setCustomApiUrl } from '../api/client';

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void;
}>();

const urlActual = ref(resolveApiBase());
const urlInput = ref(urlActual.value);
const probando = ref(false);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      urlActual.value = resolveApiBase();
      urlInput.value = urlActual.value;
    }
  }
);

async function guardarYProbar() {
  const aplicada = setCustomApiUrl(urlInput.value);
  urlActual.value = aplicada;
  probando.value = true;
  try {
    await axios.get(`${aplicada}/configuracion-pago`, { timeout: 6000 });
    Notify.create({
      type: 'positive',
      message: `Conectado correctamente a ${aplicada}`,
    });
    emit('update:modelValue', false);
  } catch (e) {
    Notify.create({
      type: 'negative',
      message: `No se pudo alcanzar ${aplicada}. Verifica Wi-Fi y que el servidor esté encendido.`,
    });
  } finally {
    probando.value = false;
  }
}

function restaurar() {
  const def = setCustomApiUrl('');
  urlActual.value = def;
  urlInput.value = def;
  api.defaults.baseURL = def;
  Notify.create({ type: 'info', message: `Servidor restaurado a ${def}` });
}
</script>
