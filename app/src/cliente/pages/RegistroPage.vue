<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <q-page class="auth-bg-cliente q-pa-md flex flex-center">
        <div class="full-width" style="max-width: 430px;">
          <q-card flat class="auth-card-light q-pa-lg q-pa-sm-xl">
            <div class="text-center q-mb-md">
              <div class="brand-squircle q-mb-md">
                <q-icon name="person_add_alt_1" color="white" size="30px" />
              </div>
              <div class="text-h5 text-weight-bolder text-grey-10">
                Crea tu cuenta
              </div>
              <div class="text-body2 text-grey-6 q-mt-xs">
                Solo toma un minuto · Envío a toda Bolivia
              </div>
            </div>

            <div class="row justify-center q-gutter-x-sm q-mb-md">
              <q-chip dense color="indigo-1" text-color="primary" icon="local_shipping" label="Seguimiento en vivo" />
              <q-chip dense color="teal-1" text-color="teal-9" icon="qr_code_2" label="QR o Efectivo" />
            </div>

            <q-form @submit="crearCuenta" class="q-gutter-y-sm">
              <div>
                <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">Nombre completo *</div>
                <q-input
                  v-model="nombre"
                  outlined
                  dense
                  bg-color="white"
                  placeholder="Ej: Juan Pérez"
                  hide-bottom-space
                  :rules="[val => !!val || 'El nombre es obligatorio']"
                >
                  <template #prepend>
                    <q-icon name="badge" color="primary" />
                  </template>
                </q-input>
              </div>

              <div>
                <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">Teléfono celular *</div>
                <q-input
                  v-model="telefono"
                  outlined
                  dense
                  bg-color="white"
                  placeholder="Ej: 71234567"
                  type="tel"
                  hide-bottom-space
                  :rules="[val => !!val || 'El teléfono es obligatorio']"
                >
                  <template #prepend>
                    <q-icon name="phone_iphone" color="primary" />
                  </template>
                </q-input>
              </div>

              <div>
                <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">Correo electrónico (opcional)</div>
                <q-input
                  v-model="email"
                  outlined
                  dense
                  bg-color="white"
                  placeholder="correo@ejemplo.com"
                  type="email"
                >
                  <template #prepend>
                    <q-icon name="mail_outline" color="grey-6" />
                  </template>
                </q-input>
              </div>

              <div>
                <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">Contraseña (mín. 6 caracteres) *</div>
                <q-input
                  v-model="password"
                  outlined
                  dense
                  bg-color="white"
                  placeholder="Crea una contraseña segura"
                  hide-bottom-space
                  :type="verPassword ? 'text' : 'password'"
                  :rules="[
                    val => !!val || 'La contraseña es obligatoria',
                    val => val.length >= 6 || 'Mínimo 6 caracteres'
                  ]"
                >
                  <template #prepend>
                    <q-icon name="lock_outline" color="primary" />
                  </template>
                  <template #append>
                    <q-icon
                      :name="verPassword ? 'visibility_off' : 'visibility'"
                      color="grey-6"
                      class="cursor-pointer"
                      @click="verPassword = !verPassword"
                    />
                  </template>
                </q-input>
              </div>

              <q-btn
                unelevated
                no-caps
                label="Crear mi cuenta"
                icon-right="check_circle"
                type="submit"
                :loading="authStore.cargando"
                class="full-width btn-cta-gradient text-weight-bold q-mt-md"
                style="min-height: 50px; font-size: 15px;"
              />
            </q-form>

            <q-separator class="q-my-md" />

            <div class="text-center column q-gutter-y-xs">
              <div class="text-body2 text-grey-7">
                ¿Ya tienes cuenta?
                <q-btn
                  flat
                  dense
                  no-caps
                  color="primary"
                  label="Inicia sesión"
                  to="/login"
                  class="text-weight-bolder"
                />
              </div>
              <div>
                <q-btn
                  flat
                  dense
                  no-caps
                  color="grey-6"
                  icon="arrow_back"
                  label="Volver al catálogo"
                  to="/inicio"
                  size="sm"
                />
              </div>
            </div>
          </q-card>
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../shared/stores/auth';
import { Notify } from 'quasar';

const router = useRouter();
const authStore = useAuthStore();

const nombre = ref('');
const telefono = ref('');
const email = ref('');
const password = ref('');
const verPassword = ref(false);

async function crearCuenta() {
  try {
    await authStore.registro({
      nombre: nombre.value.trim(),
      telefono: telefono.value.trim().replace(/[\s\-]/g, ''),
      email: email.value.trim() || undefined,
      password: password.value,
    });
    Notify.create({
      type: 'positive',
      message: '¡Cuenta creada con éxito!',
    });
    router.replace('/inicio');
  } catch (err: any) {
    const errObj = err.response?.data?.errors;
    const firstFieldErr = errObj ? Object.values(errObj)[0] : null;
    const msg = (Array.isArray(firstFieldErr) ? firstFieldErr[0] : firstFieldErr) ||
      err.response?.data?.message ||
      'Error al crear la cuenta';
    Notify.create({
      type: 'negative',
      message: msg,
    });
  }
}
</script>
