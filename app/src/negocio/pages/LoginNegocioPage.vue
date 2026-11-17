<template>
  <q-layout view="lHh Lpr lFf">
    <q-page-container>
      <q-page class="auth-bg-negocio q-pa-md flex flex-center text-white">
        <div class="full-width" style="max-width: 420px;">
          <q-card flat class="auth-card-dark q-pa-lg text-white">
            <!-- Emblema corporativo -->
            <div class="text-center q-mb-lg">
              <div class="brand-squircle q-mb-md">
                <q-icon name="storefront" color="white" size="30px" />
              </div>
              <div class="text-h5 text-weight-bolder text-white">
                Centro Operativo
              </div>
              <div class="text-caption text-grey-4 q-mt-xs">
                Admin · Operador · Repartidor
              </div>
            </div>

            <q-form @submit="iniciarSesion" class="q-gutter-y-md">
              <div>
                <div class="text-caption text-weight-bold text-grey-4 q-mb-xs">
                  Teléfono corporativo
                </div>
                <q-input
                  v-model="telefono"
                  outlined
                  dark
                  placeholder="Ej: 70000001"
                  type="tel"
                  hide-bottom-space
                  :rules="[val => !!val || 'El teléfono es obligatorio']"
                >
                  <template #prepend>
                    <q-icon name="badge" color="light-blue-3" />
                  </template>
                </q-input>
              </div>

              <div>
                <div class="row items-center justify-between q-mb-xs">
                  <span class="text-caption text-weight-bold text-grey-4">Contraseña</span>
                  <q-btn
                    flat
                    dense
                    no-caps
                    size="sm"
                    color="light-blue-3"
                    label="¿Olvidaste tu contraseña?"
                    @click="abrirRecuperar = true"
                  />
                </div>
                <q-input
                  v-model="password"
                  outlined
                  dark
                  placeholder="••••••••"
                  hide-bottom-space
                  :type="verPassword ? 'text' : 'password'"
                  :rules="[val => !!val || 'La contraseña es obligatoria']"
                >
                  <template #prepend>
                    <q-icon name="lock_outline" color="light-blue-3" />
                  </template>
                  <template #append>
                    <q-icon
                      :name="verPassword ? 'visibility_off' : 'visibility'"
                      color="grey-4"
                      class="cursor-pointer"
                      @click="verPassword = !verPassword"
                    />
                  </template>
                </q-input>
              </div>

              <q-btn
                unelevated
                no-caps
                label="Entrar al Panel"
                icon-right="arrow_forward"
                type="submit"
                :loading="authStore.cargando"
                class="full-width btn-cta-gradient text-weight-bold q-mt-sm"
                style="min-height: 50px; font-size: 15px;"
              />

              <q-btn
                outline
                no-caps
                color="teal-3"
                icon="fingerprint"
                label="Acceso con Huella / Face ID"
                class="full-width text-weight-medium"
                style="min-height: 44px;"
                @click="desbloquearBiometrico"
              />
            </q-form>

            <!-- Cuentas demo rápidas por rol -->
            <div class="q-mt-lg q-pa-sm rounded-borders" style="background: rgba(255, 255, 255, 0.04); border: 1px dashed rgba(255, 255, 255, 0.14);">
              <div class="text-caption text-grey-4 text-center q-mb-xs">
                Cuentas de prueba rápida (1 clic)
              </div>
              <div class="row q-gutter-xs justify-center">
                <q-btn dense no-caps size="sm" unelevated color="indigo-6" icon="shield" label="Admin" class="q-px-sm" @click="llenar('70000001', 'password123')" />
                <q-btn dense no-caps size="sm" unelevated color="teal-7" icon="support_agent" label="Operador" class="q-px-sm" @click="llenar('70000002', 'password123')" />
                <q-btn dense no-caps size="sm" unelevated color="amber-8" icon="two_wheeler" label="Repartidor" class="q-px-sm" @click="llenar('70000003', 'password123')" />
              </div>
            </div>

            <div class="row items-center justify-center text-caption text-grey-5 q-mt-lg">
              <q-icon name="verified_user" size="15px" color="teal-4" class="q-mr-xs" />
              <span>Conexión cifrada y sesiones auditadas por rol</span>
            </div>

            <div class="text-center q-mt-xs">
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
    const data = await authStore.login({
      telefono: telefono.value.trim(),
      password: password.value,
    });

    Notify.create({
      type: 'positive',
      message: `¡Bienvenido ${data.usuario.nombre}!`,
    });

    if (data.usuario.rol === 'repartidor') {
      router.replace('/negocio/repartidor');
    } else {
      router.replace('/negocio/dashboard');
    }
  } catch (err: any) {
    const msg =
      err.response?.data?.errors?.telefono?.[0] ||
      err.response?.data?.errors?.password?.[0] ||
      err.response?.data?.message ||
      'Acceso denegado. Verifica tus credenciales.';
    Notify.create({
      type: 'negative',
      message: msg,
    });
  }
}

function desbloquearBiometrico() {
  // Simulación / integración de biometría rápida
  Notify.create({
    type: 'info',
    message: 'Sensor biométrico activado. Introduce tus credenciales si es la primera vez en este dispositivo.',
  });
}
</script>
