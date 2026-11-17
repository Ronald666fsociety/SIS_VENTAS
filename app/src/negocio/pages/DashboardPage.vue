<template>
  <q-page class="q-pa-md">
    <q-pull-to-refresh @refresh="refrescar">
      <div v-if="cargando" class="column items-center q-pa-xl">
        <q-spinner color="primary" size="48px" />
        <div class="text-caption text-grey-7 q-mt-md">Actualizando métricas...</div>
      </div>

      <div v-else class="column q-gutter-y-md">
        <!-- Saludo e info rápida -->
        <div class="row justify-between items-center">
          <div>
            <div class="section-eyebrow">Panel Operativo</div>
            <div class="text-h5 text-weight-bolder text-grey-10 q-mt-xs">
              Hola, {{ authStore.user?.nombre }}
            </div>
            <div class="text-caption text-grey-6">
              Resumen de actividad en tiempo real
            </div>
          </div>
          <q-btn flat round dense icon="refresh" color="primary" @click="cargarMetricas" />
        </div>

        <UbicacionActualChip />

        <!-- Tarjeta URGENTE: Pagos por verificar -->
        <q-card
          flat
          class="rounded-borders cursor-pointer card-interactive text-white"
          :style="resumen.pagos_por_verificar > 0
            ? 'background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%); box-shadow: 0 12px 28px -8px rgba(239, 68, 68, 0.45);'
            : 'background: linear-gradient(135deg, #059669 0%, #10b981 100%); box-shadow: 0 12px 28px -8px rgba(16, 185, 129, 0.4);'"
          @click="irA('/negocio/pagos')"
        >
          <q-card-section class="row items-center justify-between q-pa-md">
            <div>
              <div class="text-caption text-uppercase text-weight-bold" style="letter-spacing: 0.06em; opacity: 0.9;">
                {{ resumen.pagos_por_verificar > 0 ? 'Requiere Atención Inmediata' : 'Todo al Día' }}
              </div>
              <div class="text-h3 text-weight-bolder tabular-nums q-my-xs">
                {{ resumen.pagos_por_verificar }}
              </div>
              <div class="text-subtitle2">Pagos QR por verificar</div>
            </div>
            <div class="q-pa-sm rounded-borders" style="background: rgba(255, 255, 255, 0.18);">
              <q-icon
                :name="resumen.pagos_por_verificar > 0 ? 'priority_high' : 'check_circle'"
                size="38px"
              />
            </div>
          </q-card-section>
        </q-card>

        <!-- Grilla de 2 columnas con tarjetas de métricas -->
        <div class="row q-col-gutter-sm q-col-gutter-sm-md">
          <!-- Ventas del día -->
          <div class="col-6">
            <q-card
              flat
              bordered
              class="card-interactive bg-white cursor-pointer full-height"
              @click="irA('/negocio/mas?seccion=reportes')"
            >
              <q-card-section class="q-pa-md">
                <div class="row items-center justify-between text-primary q-mb-xs">
                  <span class="section-eyebrow text-primary">VENTAS HOY</span>
                  <q-avatar size="28px" color="indigo-1" text-color="primary" icon="payments" />
                </div>
                <div class="text-h5 text-weight-bolder text-grey-10 tabular-nums q-mt-xs">
                  Bs {{ resumen.ventas_hoy }}
                </div>
                <div class="text-caption text-grey-6">Ventas confirmadas</div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Pedidos de hoy -->
          <div class="col-6">
            <q-card
              flat
              bordered
              class="card-interactive bg-white cursor-pointer full-height"
              @click="irA('/negocio/pedidos?hoy=1')"
            >
              <q-card-section class="q-pa-md">
                <div class="row items-center justify-between q-mb-xs">
                  <span class="section-eyebrow text-indigo-7">PEDIDOS HOY</span>
                  <q-avatar size="28px" color="blue-1" text-color="indigo-7" icon="receipt" />
                </div>
                <div class="text-h5 text-weight-bolder text-grey-10 tabular-nums q-mt-xs">
                  {{ resumen.pedidos_hoy }}
                </div>
                <div class="text-caption text-grey-6">Total recibidos</div>
              </q-card-section>
            </q-card>
          </div>

          <!-- En preparación -->
          <div class="col-6">
            <q-card
              flat
              bordered
              class="card-interactive bg-white cursor-pointer full-height"
              @click="irA('/negocio/pedidos?estado=preparando')"
            >
              <q-card-section class="q-pa-md">
                <div class="row items-center justify-between q-mb-xs">
                  <span class="section-eyebrow text-amber-9">POR PREPARAR</span>
                  <q-avatar size="28px" color="amber-1" text-color="amber-9" icon="inventory_2" />
                </div>
                <div class="text-h5 text-weight-bolder text-grey-10 tabular-nums q-mt-xs">
                  {{ resumen.por_preparar }}
                </div>
                <div class="text-caption text-grey-6">En empaque / despacho</div>
              </q-card-section>
            </q-card>
          </div>

          <!-- Stock bajo -->
          <div class="col-6">
            <q-card
              flat
              bordered
              class="card-interactive bg-white cursor-pointer full-height"
              @click="irA('/negocio/catalogo?stock_bajo=1')"
            >
              <q-card-section class="q-pa-md">
                <div class="row items-center justify-between q-mb-xs">
                  <span class="section-eyebrow text-deep-orange-8">STOCK BAJO</span>
                  <q-avatar size="28px" color="deep-orange-1" text-color="deep-orange-8" icon="warning" />
                </div>
                <div class="text-h5 text-weight-bolder text-grey-10 tabular-nums q-mt-xs">
                  {{ resumen.stock_bajo }}
                </div>
                <div class="text-caption text-grey-6">Productos por reponer</div>
              </q-card-section>
            </q-card>
          </div>
        </div>

        <!-- Acciones rápidas -->
        <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md q-mt-xs">
          <div class="text-subtitle2 text-weight-bold q-mb-sm text-grey-8">Acciones Rápidas</div>
          <div class="row q-gutter-sm">
            <q-btn
              unelevated
              color="primary"
              icon="receipt_long"
              label="Ver todos los pedidos"
              to="/negocio/pedidos"
              class="col text-weight-bold"
              style="min-height: 44px;"
            />
            <q-btn
              v-if="authStore.esAdmin"
              outline
              color="primary"
              icon="add_box"
              label="Nuevo producto"
              to="/negocio/catalogo"
              class="col text-weight-bold"
              style="min-height: 44px;"
            />
          </div>
        </q-card>
      </div>
    </q-pull-to-refresh>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { api } from '../../shared/api/client';
import { useAuthStore } from '../../shared/stores/auth';
import UbicacionActualChip from '../../shared/components/UbicacionActualChip.vue';
import type { DashboardResumen } from '../../shared/types';

const router = useRouter();
const authStore = useAuthStore();
const cargando = ref(true);

const resumen = ref<DashboardResumen>({
  pagos_por_verificar: 0,
  pedidos_hoy: 0,
  ventas_hoy: '0.00',
  stock_bajo: 0,
  por_preparar: 0,
  listos_para_envio: 0,
  en_camino: 0,
});

onMounted(() => {
  cargarMetricas();
});

async function cargarMetricas() {
  try {
    const resp = await api.get('/admin/dashboard');
    resumen.value = resp.data;
  } catch (e) {
    console.error(e);
  } finally {
    cargando.value = false;
  }
}

async function refrescar(done: () => void) {
  await cargarMetricas();
  done();
}

function irA(path: string) {
  router.push(path);
}
</script>

<style scoped>
.opacity-80 {
  opacity: 0.85;
}
</style>
