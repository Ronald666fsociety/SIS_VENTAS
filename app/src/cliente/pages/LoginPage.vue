<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <q-page class="auth-bg-cliente q-pa-md flex flex-center">
        <div class="full-width" style="max-width: 416px;">
          <q-card flat class="auth-card-light q-pa-lg q-pa-sm-xl">
            <!-- Cabecera amigable con emblema de marca -->
            <div class="text-center q-mb-lg">
              <div class="brand-squircle q-mb-md">
                <q-icon name="shopping_bag" color="white" size="30px" />
              </div>
              <div class="text-h5 text-weight-bolder text-grey-10">
                ¡Qué bueno verte!
              </div>
              <div class="text-body2 text-grey-6 q-mt-xs">
                Ingresa a tu cuenta y sigue tus pedidos al instante
              </div>
            </div>

            <q-form @submit="iniciarSesion" class="q-gutter-y-md">
              <div>
                <div class="text-caption text-weight-bold text-grey-8 q-mb-xs">
                  Teléfono celular
                </div>
                <q-input
                  v-model="telefono"
                  outlined
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
                <div class="row items-center justify-between q-mb-xs">
                  <span class="text-caption text-weight-bold text-grey-8">Contraseña</span>
                  <q-btn
                    flat
                    dense
                    no-caps
                    size="sm"
                    color="primary"
                    label="¿Olvidaste tu contraseña?"
                    class="text-weight-bold"
                    @click="abrirRecuperar = true"
                  />
                </div>
                <q-input
                  v-model="password"
                  outlined
                  bg-color="white"
                  placeholder="Tu contraseña"
                  hide-bottom-space
                  :type="verPassword ? 'text' : 'password'"
                  :rules="[val => !!val || 'La contraseña es obligatoria']"
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
                label="Entrar a mi cuenta"
                icon-right="arrow_forward"
                type="submit"
                :loading="authStore.cargando"
                class="full-width btn-cta-gradient text-weight-bold q-mt-sm"
                style="min-height: 50px; font-size: 15px;"
              />
            </q-form>

            <!-- Acceso demo rápido -->
            <div class="surface-soft q-pa-sm q-mt-lg row items-center justify-between" style="border: 1px dashed rgba(79, 70, 229, 0.25);">
              <div class="row items-center q-gutter-x-xs text-caption text-grey-7">
                <q-icon name="bolt" color="amber-8" size="16px" />
                <span class="text-weight-medium">Acceso rápido demo</span>
              </div>
              <q-btn
                dense
                no-caps
                unelevated
                size="sm"
                color="indigo-1"
                text-color="primary"
                icon="person"
                label="70000004"
                class="q-px-sm text-weight-bold"
                @click="llenar('70000004', 'password123')"
              />
            </div>

            <q-separator class="q-my-lg" />

            <div class="text-center column q-gutter-y-xs">
              <div class="text-body2 text-grey-7">
                ¿Primera vez aquí?
                <q-btn
                  flat
                  dense
                  no-caps
                  color="primary"
                  label="Crear cuenta gratis"
                  to="/registro"
                  class="text-weight-bolder"
                />
              </div>
              <div>
                <q-btn
                  flat
                  dense
                  no-caps
                  color="grey-6"
                  icon="storefront"
                  label="Explorar catálogo como invitado"
                  to="/inicio"
                  size="sm"
                />
              </div>
              <div>
                <q-btn
                  flat
                  dense
                  no-caps
                  color="grey-5"
                  icon="dns"
                  label="Configurar servidor (IP)"
                  size="xs"
                  @click="abrirServidor = true"
                />
              </div>
            </div>
          </q-card>
        </div>
      </q-page>
    </q-page-container>

    <RecuperarPasswordDialog v-model="abrirRecuperar" />
    <ServerConfigDialog v-model="abrirServidor" />
  </q-layout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../../shared/stores/auth';
import { Notify } from 'quasar';
import RecuperarPasswordDialog from '../../shared/components/RecuperarPasswordDialog.vue';
import ServerConfigDialog from '../../shared/components/ServerConfigDialog.vue';

const router = useRouter();
const authStore = useAuthStore();

const telefono = ref('');
const password = ref('');
const verPassword = ref(false);
const abrirRecuperar = ref(false);
const abrirServidor = ref(false);

function llenar(t: string, p: string) {
  telefono.value = t;
  password.value = p;
}

async function iniciarSesion() {
  try {
    await authStore.login({
      telefono: telefono.value.trim(),
      password: password.value,
    });
    Notify.create({
      type: 'positive',
      message: '¡Bienvenido de nuevo!',
    });
    router.replace('/inicio');
  } catch (err: any) {
    const msg =
      err.response?.data?.errors?.telefono?.[0] ||
      err.response?.data?.errors?.password?.[0] ||
      err.response?.data?.message ||
      'Teléfono o contraseña incorrectos';
    Notify.create({
      type: 'negative',
      message: msg,
    });
  }
}
</script>
