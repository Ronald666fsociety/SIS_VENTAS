<template>
  <q-dialog
    :model-value="modelValue"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <q-card class="rounded-xl q-pa-lg shadow-4" style="min-width: 340px; max-width: 92vw;">
      <div class="row items-center justify-between q-mb-md">
        <div class="row items-center q-gutter-x-sm">
          <div class="brand-squircle-sm">
            <q-icon name="lock_reset" size="20px" />
          </div>
          <div>
            <div class="text-subtitle1 text-weight-bolder text-grey-10">Recuperar acceso</div>
            <div class="text-caption text-grey-6">Paso {{ paso }} de 2</div>
          </div>
        </div>
        <q-btn flat round dense icon="close" color="grey-7" v-close-popup />
      </div>

      <!-- Paso 1: Solicitar código -->
      <q-form v-if="paso === 1" @submit="solicitarCodigo" class="q-gutter-y-md">
        <div class="text-caption text-grey-7">
          Ingresa tu teléfono registrado para generar un código de recuperación de 6 dígitos.
        </div>

        <q-input
          v-model="telefono"
          outlined
          dense
          type="tel"
          label="Teléfono celular *"
          placeholder="Ej: 70000004"
          :rules="[val => !!val || 'El teléfono es obligatorio']"
        >
          <template #prepend>
            <q-icon name="phone" />
          </template>
        </q-input>

        <q-btn
          unelevated
          color="primary"
          label="Enviar código"
          type="submit"
          :loading="procesando"
          class="full-width text-weight-bold"
        />
      </q-form>

      <!-- Paso 2: Ingresar código + nueva clave -->
      <q-form v-else @submit="confirmarRestablecer" class="q-gutter-y-md">
        <q-banner v-if="codigoDev" rounded dense class="bg-blue-1 text-primary">
          <template #avatar>
            <q-icon name="vpn_key" color="primary" />
          </template>
          Código generado (modo prueba): <b class="text-subtitle1">{{ codigoDev }}</b>
        </q-banner>
        <div v-else class="text-caption text-grey-7">
          Ingresa el código de 6 dígitos enviado a <b>{{ telefono }}</b> y tu nueva contraseña.
        </div>

        <q-input
          v-model="codigo"
          outlined
          dense
          maxlength="6"
          label="Código de 6 dígitos *"
          placeholder="123456"
          :rules="[val => val.length === 6 || 'Debe tener 6 dígitos']"
        />

        <q-input
          v-model="nuevaPassword"
          outlined
          dense
          :type="verClave ? 'text' : 'password'"
          label="Nueva contraseña (mín. 6 caracteres) *"
          :rules="[val => val.length >= 6 || 'Mínimo 6 caracteres']"
        >
          <template #append>
            <q-icon
              :name="verClave ? 'visibility_off' : 'visibility'"
              class="cursor-pointer"
              @click="verClave = !verClave"
            />
          </template>
        </q-input>

        <div class="row q-gutter-x-sm">
          <q-btn flat label="Atrás" class="col-4" @click="paso = 1" />
          <q-btn
            unelevated
            color="positive"
            label="Cambiar clave"
            type="submit"
            :loading="procesando"
            class="col text-weight-bold"
          />
        </div>
      </q-form>
    </q-card>
  </q-dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { api } from '../api/client';
import { Notify } from 'quasar';

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', val: boolean): void;
}>();

const paso = ref<1 | 2>(1);
const telefono = ref('');
const codigo = ref('');
const nuevaPassword = ref('');
const codigoDev = ref<string | null>(null);
const verClave = ref(false);
const procesando = ref(false);

watch(
  () => props.modelValue,
  (abierto) => {
    if (abierto) {
      paso.value = 1;
      codigo.value = '';
      nuevaPassword.value = '';
      codigoDev.value = null;
    }
  }
);

async function solicitarCodigo() {
  procesando.value = true;
  try {
    const resp = await api.post('/auth/recuperar', {
      telefono: telefono.value.trim().replace(/[\s\-]/g, ''),
    });
    codigoDev.value = resp.data?.codigo_dev || null;
    if (codigoDev.value) {
      codigo.value = codigoDev.value;
    }
    Notify.create({
      type: 'info',
      message: resp.data?.message || 'Código enviado.',
    });
    paso.value = 2;
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'No se pudo solicitar el código',
    });
  } finally {
    procesando.value = false;
  }
}

async function confirmarRestablecer() {
  procesando.value = true;
  try {
    const resp = await api.post('/auth/restablecer', {
      telefono: telefono.value.trim().replace(/[\s\-]/g, ''),
      codigo: codigo.value.trim(),
      password: nuevaPassword.value,
    });
    Notify.create({
      type: 'positive',
      message: resp.data?.message || 'Contraseña actualizada. Inicia sesión.',
    });
    emit('update:modelValue', false);
  } catch (err: any) {
    const errObj = err.response?.data?.errors;
    const firstErr = errObj ? Object.values(errObj)[0] : null;
    const msg = (Array.isArray(firstErr) ? firstErr[0] : firstErr) ||
      err.response?.data?.message ||
      'Código incorrecto o vencido';
    Notify.create({
      type: 'negative',
      message: msg,
    });
  } finally {
    procesando.value = false;
  }
}
</script>
