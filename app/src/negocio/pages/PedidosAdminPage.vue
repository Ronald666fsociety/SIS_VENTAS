<template>
  <q-page class="q-pa-md">
    <div class="row justify-between items-center q-mb-md">
      <div>
        <div class="text-h6 text-weight-bolder text-grey-9">Gestión de Pedidos</div>
        <div class="text-caption text-grey-6">Verificación de pago, preparación y despacho</div>
      </div>
      <q-btn flat round dense icon="refresh" color="grey-7" @click="cargarPedidos" />
    </div>

    <!-- Chips de filtros -->
    <div class="row no-wrap scroll q-gutter-x-xs q-mb-md no-scrollbar">
      <q-chip
        clickable
        :selected="!filtroEstado && !filtroHoy"
        color="primary"
        :text-color="!filtroEstado && !filtroHoy ? 'white' : 'primary'"
        :outline="!!filtroEstado || filtroHoy"
        label="Todos"
        @click="limpiarFiltros"
      />
      <q-chip
        clickable
        :selected="filtroHoy"
        color="indigo-8"
        :text-color="filtroHoy ? 'white' : 'indigo-8'"
        :outline="!filtroHoy"
        label="Hoy"
        icon="today"
        @click="toggleHoy"
      />
      <q-chip
        clickable
        :selected="filtroEstado === 'pago_en_revision'"
        color="red-7"
        :text-color="filtroEstado === 'pago_en_revision' ? 'white' : 'red-7'"
        :outline="filtroEstado !== 'pago_en_revision'"
        label="Pago por verificar"
        icon="priority_high"
        @click="seleccionarEstado('pago_en_revision')"
      />
      <q-chip
        clickable
        :selected="filtroEstado === 'pendiente_pago'"
        color="amber-9"
        :text-color="filtroEstado === 'pendiente_pago' ? 'white' : 'amber-9'"
        :outline="filtroEstado !== 'pendiente_pago'"
        label="Pendiente pago"
        @click="seleccionarEstado('pendiente_pago')"
      />
      <q-chip
        clickable
        :selected="filtroEstado === 'preparando'"
        color="cyan-9"
        :text-color="filtroEstado === 'preparando' ? 'white' : 'cyan-9'"
        :outline="filtroEstado !== 'preparando'"
        label="Preparando"
        @click="seleccionarEstado('preparando')"
      />
      <q-chip
        clickable
        :selected="filtroEstado === 'listo_para_envio'"
        color="indigo-7"
        :text-color="filtroEstado === 'listo_para_envio' ? 'white' : 'indigo-7'"
        :outline="filtroEstado !== 'listo_para_envio'"
        label="Listos p/ envío"
        @click="seleccionarEstado('listo_para_envio')"
      />
      <q-chip
        clickable
        :selected="filtroEstado === 'en_camino'"
        color="teal-8"
        :text-color="filtroEstado === 'en_camino' ? 'white' : 'teal-8'"
        :outline="filtroEstado !== 'en_camino'"
        label="En camino"
        @click="seleccionarEstado('en_camino')"
      />
      <q-chip
        clickable
        :selected="filtroEstado === 'entregado'"
        color="positive"
        :text-color="filtroEstado === 'entregado' ? 'white' : 'positive'"
        :outline="filtroEstado !== 'entregado'"
        label="Entregados"
        @click="seleccionarEstado('entregado')"
      />
    </div>

    <!-- Buscador rápido por código o cliente -->
    <q-input
      v-model="busquedaTexto"
      outlined
      dense
      rounded
      bg-color="white"
      placeholder="Buscar por código, nombre o teléfono..."
      class="q-mb-md"
      debounce="300"
      @update:model-value="cargarPedidos"
      clearable
    >
      <template #prepend>
        <q-icon name="search" />
      </template>
    </q-input>

    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando pedidos...</div>
    </div>

    <!-- Lista de pedidos -->
    <div v-else-if="pedidos.length > 0" class="column q-gutter-y-md">
      <q-card
        v-for="p in pedidos"
        :key="p.id"
        flat
        bordered
        class="card-interactive bg-white cursor-pointer"
        @click="abrirDetalle(p)"
      >
        <q-card-section>
          <div class="row justify-between items-center q-mb-xs">
            <span class="text-subtitle1 text-weight-bolder text-grey-10 font-display tabular-nums">
              #{{ p.codigo }}
            </span>
            <EstadoBadge :estado="p.estado" :etiqueta="p.estado_etiqueta" />
          </div>

          <div class="row justify-between items-center text-body2 text-grey-9 q-mt-xs">
            <span class="text-weight-bold">{{ p.cliente?.nombre || 'Cliente' }}</span>
            <span class="text-subtitle1 text-weight-bolder text-primary tabular-nums font-display">
              Bs {{ p.total }}
            </span>
          </div>

          <div class="text-caption text-grey-7 q-mt-xs ellipsis">
            <q-icon name="place" size="14px" /> {{ p.direccion_texto }}
          </div>

          <div v-if="p.repartidor" class="text-caption text-teal-9 text-weight-medium q-mt-xs">
            <q-icon name="two_wheeler" size="14px" /> Repartidor: {{ p.repartidor.nombre }}
          </div>

          <div class="text-caption text-grey-5 q-mt-xs">
            {{ formatearFecha(p.created_at) }}
          </div>
        </q-card-section>

        <!-- Barra rápida si el pedido tiene un comprobante pendiente de revisión -->
        <q-card-actions
          v-if="p.estado === 'pago_en_revision' || p.pago?.estado === 'en_revision'"
          align="between"
          class="bg-amber-1 q-px-md"
        >
          <span class="text-caption text-weight-bold text-amber-10">
            <q-icon name="receipt_long" size="16px" /> Comprobante listo para revisar
          </span>
          <q-btn
            unelevated
            dense
            size="sm"
            color="positive"
            icon="check"
            label="Revisar / Aprobar"
            class="text-weight-bold q-px-sm"
            @click.stop="abrirDetalle(p)"
          />
        </q-card-actions>
      </q-card>
    </div>

    <div v-else class="column items-center q-pa-xl text-center text-grey-6">
      <q-icon name="inbox" size="64px" />
      <div class="text-subtitle1 q-mt-md">No hay pedidos con estos filtros</div>
    </div>

    <!-- Modal detalle de pedido y acciones de todo el flujo -->
    <q-dialog v-model="modalDetalle" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card v-if="pedidoSeleccionado" class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">
            Pedido #{{ pedidoSeleccionado.codigo }}
          </q-toolbar-title>
          <EstadoBadge :estado="pedidoSeleccionado.estado" :etiqueta="pedidoSeleccionado.estado_etiqueta" class="q-mr-sm" />
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <!-- 1. SECCIÓN DE PAGO Y VERIFICACIÓN DE COMPROBANTE -->
          <q-card
            v-if="pedidoSeleccionado.pago"
            flat
            bordered
            class="rounded-borders bg-white shadow-1 q-pa-md q-mb-md"
          >
            <div class="row justify-between items-center q-mb-sm">
              <div class="text-subtitle1 text-weight-bold row items-center">
                <q-icon :name="pedidoSeleccionado.pago.metodo === 'efectivo' ? 'payments' : 'qr_code_scanner'" color="primary" size="22px" class="q-mr-xs" />
                {{ pedidoSeleccionado.pago.metodo === 'efectivo' ? 'Pago en Efectivo Contra Entrega' : 'Verificación de Pago QR' }}
              </div>
              <EstadoBadge :estado="pedidoSeleccionado.pago.estado" />
            </div>

            <div class="row justify-between text-body2 q-mb-xs">
              <span class="text-grey-7">Monto esperado:</span>
              <span class="text-weight-bolder text-primary text-subtitle1">Bs {{ pedidoSeleccionado.total }}</span>
            </div>

            <div v-if="pedidoSeleccionado.pago.nro_transaccion" class="row justify-between text-caption q-mb-sm">
              <span class="text-grey-7">Nro. de Transacción:</span>
              <span class="text-weight-bold text-grey-9">{{ pedidoSeleccionado.pago.nro_transaccion }}</span>
            </div>

            <!-- Imagen del comprobante subido por el cliente (solo si es QR) -->
            <template v-if="pedidoSeleccionado.pago.metodo !== 'efectivo'">
              <div v-if="pedidoSeleccionado.pago.comprobante_url" class="q-my-sm text-center bg-grey-9 q-pa-sm rounded-borders">
                <div class="text-caption text-white q-mb-xs">Comprobante enviado por el cliente:</div>
                <img
                  :src="pedidoSeleccionado.pago.comprobante_url"
                  alt="Comprobante de pago"
                  class="rounded-borders cursor-pointer"
                  style="max-height: 280px; max-width: 100%; object-fit: contain;"
                  @click="verImagenCompleta(pedidoSeleccionado.pago.comprobante_url)"
                />
              </div>
              <div v-else class="bg-amber-1 text-amber-10 q-pa-sm rounded-borders text-caption q-my-sm">
                El cliente aún no ha subido la imagen del comprobante en la app.
              </div>

              <!-- Botones de Aprobar / Rechazar Pago directamente desde aquí -->
              <div
                v-if="pedidoSeleccionado.pago.estado === 'en_revision' || pedidoSeleccionado.estado === 'pago_en_revision' || pedidoSeleccionado.estado === 'pendiente_pago'"
                class="row q-gutter-x-sm q-mt-md"
              >
                <q-btn
                  v-if="pedidoSeleccionado.pago.estado === 'en_revision'"
                  outline
                  color="negative"
                  icon="close"
                  label="Rechazar Pago"
                  class="col text-weight-bold"
                  style="min-height: 44px;"
                  :loading="procesandoAccion"
                  @click="abrirModalRechazoPago"
                />
                <q-btn
                  unelevated
                  color="positive"
                  icon="verified"
                  label="Aprobar Pago y Preparar"
                  class="col text-weight-bolder shadow-2"
                  style="min-height: 44px;"
                  :loading="procesandoAccion"
                  @click="aprobarPagoDelPedido"
                />
              </div>
            </template>
            <div v-else class="bg-green-1 text-positive q-pa-sm rounded-borders text-caption q-mt-xs">
              El repartidor cobrará Bs {{ pedidoSeleccionado.total }} en efectivo al entregar.
            </div>
          </q-card>

          <!-- Calificación del cliente si el pedido ya fue calificado -->
          <q-card
            v-if="pedidoSeleccionado.calificacion"
            flat
            bordered
            class="rounded-borders bg-white shadow-1 q-pa-md q-mb-md"
          >
            <div class="text-subtitle2 text-weight-bold q-mb-xs">Calificación del Cliente</div>
            <q-rating :model-value="pedidoSeleccionado.calificacion" size="24px" color="amber-8" readonly />
            <div v-if="pedidoSeleccionado.comentario_calificacion" class="text-caption text-grey-8 q-mt-xs">
              "{{ pedidoSeleccionado.comentario_calificacion }}"
            </div>
          </q-card>

          <!-- Código PIN de 4 dígitos y ubicación en vivo si está En Camino -->
          <q-card
            v-if="pedidoSeleccionado.estado === 'en_camino'"
            flat
            bordered
            class="bg-amber-1 rounded-borders q-pa-md q-mb-md text-center"
          >
            <div v-if="pedidoSeleccionado.codigo_entrega">
              <div class="text-caption text-weight-bold text-amber-10 text-uppercase">
                PIN de Entrega del Cliente
              </div>
              <div class="text-h4 text-weight-bolder text-amber-10 q-my-xs">
                {{ pedidoSeleccionado.codigo_entrega }}
              </div>
            </div>
            <div v-if="pedidoSeleccionado.repartidor_ubicacion" class="q-mt-sm text-caption text-teal-10 bg-teal-1 q-pa-xs rounded-borders">
              <q-icon name="two_wheeler" size="16px" />
              A <b>{{ pedidoSeleccionado.repartidor_ubicacion.distancia_km }} km</b> · Tiempo estimado: <b>{{ pedidoSeleccionado.repartidor_ubicacion.texto_eta }}</b>
            </div>
          </q-card>

          <!-- 2. TARJETA CLIENTE Y CONTACTO -->
          <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md q-mb-md">
            <div class="row justify-between items-center">
              <div>
                <div class="text-subtitle1 text-weight-bold">{{ pedidoSeleccionado.cliente?.nombre }}</div>
                <div class="text-caption text-grey-7">{{ pedidoSeleccionado.cliente?.telefono }}</div>
              </div>
              <div class="row q-gutter-x-xs">
                <q-btn
                  round
                  dense
                  color="positive"
                  icon="chat"
                  @click="abrirWhatsapp(pedidoSeleccionado.cliente?.whatsapp_url)"
                />
                <q-btn
                  round
                  dense
                  color="primary"
                  icon="phone"
                  :href="`tel:${pedidoSeleccionado.cliente?.telefono}`"
                />
              </div>
            </div>
          </q-card>

          <!-- 3. DIRECCIÓN Y MAPAS -->
          <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md q-mb-md">
            <div class="text-subtitle2 text-weight-bold q-mb-xs">Dirección de Entrega:</div>
            <div class="text-body2 text-grey-9">{{ pedidoSeleccionado.direccion_texto }}</div>
            <div v-if="pedidoSeleccionado.referencia" class="text-caption text-grey-7 q-mt-xs">
              <b>Referencia:</b> {{ pedidoSeleccionado.referencia }}
            </div>

            <div class="row q-gutter-x-sm q-mt-sm">
              <q-btn
                outline
                dense
                color="primary"
                icon="map"
                label="Google Maps"
                class="col"
                @click="abrirGoogleMaps(pedidoSeleccionado.lat, pedidoSeleccionado.lng)"
              />
              <q-btn
                outline
                dense
                color="cyan-8"
                icon="navigation"
                label="Waze"
                class="col"
                @click="abrirWaze(pedidoSeleccionado.lat, pedidoSeleccionado.lng)"
              />
            </div>
          </q-card>

          <!-- 4. ITEMS DEL PEDIDO -->
          <q-card flat bordered class="rounded-borders bg-white shadow-1 q-mb-md">
            <q-card-section>
              <div class="text-subtitle2 text-weight-bold q-mb-xs">Productos del Pedido:</div>
              <q-list separator>
                <q-item v-for="(it, idx) in pedidoSeleccionado.items" :key="idx" dense class="q-px-none">
                  <q-item-section>
                    <q-item-label class="text-weight-medium">{{ it.nombre_producto }}</q-item-label>
                    <q-item-label caption>{{ it.cantidad }} x Bs {{ it.precio_unitario }}</q-item-label>
                  </q-item-section>
                  <q-item-section side class="text-weight-bold text-primary">
                    Bs {{ it.subtotal }}
                  </q-item-section>
                </q-item>
              </q-list>

              <q-separator class="q-my-sm" />

              <div class="row justify-between text-caption text-grey-8">
                <span>Subtotal:</span>
                <span>Bs {{ pedidoSeleccionado.subtotal }}</span>
              </div>
              <div class="row justify-between text-caption text-grey-8">
                <span>Costo Envío:</span>
                <span>Bs {{ pedidoSeleccionado.costo_envio }}</span>
              </div>
              <div class="row justify-between text-subtitle1 text-weight-bold text-primary q-mt-xs">
                <span>Total:</span>
                <span>Bs {{ pedidoSeleccionado.total }}</span>
              </div>
            </q-card-section>
          </q-card>

          <!-- 5. REPARTIDOR ASIGNADO -->
          <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md q-mb-md">
            <div class="text-subtitle2 text-weight-bold q-mb-xs">Repartidor Asignado:</div>
            <div v-if="pedidoSeleccionado.repartidor" class="row justify-between items-center">
              <div>
                <div class="text-body2 text-weight-medium">{{ pedidoSeleccionado.repartidor.nombre }}</div>
                <div class="text-caption text-grey-7">{{ pedidoSeleccionado.repartidor.telefono }}</div>
              </div>
              <q-btn
                outline
                dense
                color="primary"
                label="Cambiar"
                size="sm"
                @click="abrirAsignarRepartidor"
              />
            </div>
            <div v-else class="row justify-between items-center">
              <span class="text-caption text-grey-6">Sin repartidor aún</span>
              <q-btn
                unelevated
                dense
                color="primary"
                label="Asignar Repartidor"
                size="sm"
                @click="abrirAsignarRepartidor"
              />
            </div>
          </q-card>
        </q-card-section>

        <!-- BOTONES DE TRANSICIÓN DE ESTADO DEL FLUJO EN LA PARTE INFERIOR FIJA -->
        <q-card-section class="bg-white shadow-2 q-pa-md">
          <div class="column q-gutter-y-sm">
            <!-- Paso 1 -> 2: Si pago_verificado -> Preparando -->
            <q-btn
              v-if="pedidoSeleccionado.estado === 'pago_verificado'"
              unelevated
              color="cyan-9"
              icon="restaurant"
              label="Iniciar Preparación del Pedido"
              class="full-width text-weight-bold"
              style="min-height: 48px;"
              :loading="procesandoAccion"
              @click="cambiarEstado('preparando')"
            />

            <!-- Paso 2 -> 3: Si preparando -> Listo para envío -->
            <q-btn
              v-if="pedidoSeleccionado.estado === 'preparando'"
              unelevated
              color="indigo-7"
              icon="inventory"
              label="Marcar como Listo para Envío"
              class="full-width text-weight-bold"
              style="min-height: 48px;"
              :loading="procesandoAccion"
              @click="cambiarEstado('listo_para_envio')"
            />

            <!-- Paso 3 -> 4: Si listo_para_envio -> Asignar repartidor / Enviar en camino -->
            <template v-if="pedidoSeleccionado.estado === 'listo_para_envio'">
              <q-btn
                v-if="!pedidoSeleccionado.repartidor"
                unelevated
                color="primary"
                icon="two_wheeler"
                label="Asignar Repartidor"
                class="full-width text-weight-bold"
                style="min-height: 46px;"
                @click="abrirAsignarRepartidor"
              />
              <q-btn
                unelevated
                color="teal-8"
                icon="local_shipping"
                label="Despachar Pedido (Marcar En Camino)"
                class="full-width text-weight-bold"
                style="min-height: 48px;"
                :loading="procesandoAccion"
                @click="cambiarEstado('en_camino')"
              />
            </template>

            <!-- Paso 4 -> 5: Si en_camino -> Marcar como Entregado -->
            <q-btn
              v-if="pedidoSeleccionado.estado === 'en_camino'"
              unelevated
              color="positive"
              icon="task_alt"
              label="Confirmar Pedido Entregado"
              class="full-width text-weight-bolder shadow-2"
              style="min-height: 48px;"
              :loading="procesandoAccion"
              @click="cambiarEstado('entregado')"
            />

            <!-- Opción de cancelar pedido si aún no está finalizado -->
            <q-btn
              v-if="!['entregado', 'cancelado'].includes(pedidoSeleccionado.estado)"
              flat
              dense
              color="negative"
              icon="cancel"
              label="Cancelar este pedido"
              class="full-width text-caption"
              :loading="procesandoAccion"
              @click="cambiarEstado('cancelado')"
            />
          </div>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Diálogo para rechazar el comprobante de pago -->
    <q-dialog v-model="modalRechazoPago">
      <q-card class="rounded-borders q-pa-md" style="min-width: 320px; max-width: 90vw;">
        <div class="text-h6 text-weight-bold text-negative q-mb-xs">Rechazar Comprobante</div>
        <div class="text-caption text-grey-7 q-mb-md">Selecciona o escribe el motivo de rechazo:</div>

        <div class="column q-gutter-y-xs q-mb-md">
          <q-btn
            v-for="opc in motivosRapidos"
            :key="opc"
            outline
            dense
            color="grey-8"
            :label="opc"
            align="left"
            class="text-weight-medium q-py-xs"
            @click="motivoRechazo = opc"
          />
        </div>

        <q-input
          v-model="motivoRechazo"
          outlined
          dense
          label="Motivo de rechazo *"
          class="q-mb-md"
        />

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" v-close-popup />
          <q-btn
            unelevated
            color="negative"
            label="Confirmar Rechazo"
            :disable="!motivoRechazo.trim()"
            :loading="procesandoAccion"
            @click="rechazarPagoDelPedido"
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Diálogo para elegir y asignar repartidor -->
    <q-dialog v-model="modalRepartidores">
      <q-card class="rounded-borders q-pa-md" style="min-width: 320px; max-width: 90vw;">
        <div class="text-h6 text-weight-bold q-mb-sm">Asignar Repartidor</div>
        <q-list separator>
          <q-item
            v-for="rep in repartidores"
            :key="rep.id"
            clickable
            v-ripple
            @click="confirmarAsignacion(rep.id)"
          >
            <q-item-section avatar>
              <q-icon name="two_wheeler" color="primary" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-bold">{{ rep.nombre }} {{ rep.apellido || '' }}</q-item-label>
              <q-item-label caption>{{ rep.telefono }}</q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
        <q-card-actions align="right">
          <q-btn flat label="Cerrar" v-close-popup />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { api } from '../../shared/api/client';
import { Notify } from 'quasar';
import EstadoBadge from '../../shared/components/EstadoBadge.vue';
import type { Pedido, EstadoPedido } from '../../shared/types';

const route = useRoute();
const pedidos = ref<Pedido[]>([]);
const cargando = ref(true);
const filtroEstado = ref<string | null>(null);
const filtroHoy = ref(false);
const busquedaTexto = ref('');
const procesandoAccion = ref(false);

const modalDetalle = ref(false);
const pedidoSeleccionado = ref<Pedido | null>(null);

const modalRepartidores = ref(false);
const repartidores = ref<any[]>([]);

const modalRechazoPago = ref(false);
const motivoRechazo = ref('');
const motivosRapidos = [
  'Monto incorrecto en el comprobante',
  'Comprobante ilegible o borroso',
  'Pago no recibido en la cuenta bancaria',
  'Número de transacción duplicado',
];

onMounted(() => {
  if (route.query.estado) {
    filtroEstado.value = String(route.query.estado);
  }
  if (route.query.hoy === '1') {
    filtroHoy.value = true;
  }
  cargarPedidos();
});

async function cargarPedidos() {
  cargando.value = true;
  try {
    const params: Record<string, any> = {};
    if (filtroEstado.value) params.estado = filtroEstado.value;
    if (filtroHoy.value) params.hoy = 1;
    if (busquedaTexto.value && busquedaTexto.value.trim()) params.q = busquedaTexto.value.trim();

    const resp = await api.get('/admin/pedidos', { params });
    pedidos.value = resp.data.data || [];
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar pedidos' });
  } finally {
    cargando.value = false;
  }
}

function limpiarFiltros() {
  filtroEstado.value = null;
  filtroHoy.value = false;
  cargarPedidos();
}

function toggleHoy() {
  filtroHoy.value = !filtroHoy.value;
  cargarPedidos();
}

function seleccionarEstado(est: string) {
  filtroEstado.value = filtroEstado.value === est ? null : est;
  cargarPedidos();
}

async function abrirDetalle(p: Pedido) {
  try {
    const resp = await api.get(`/admin/pedidos/${p.id}`);
    pedidoSeleccionado.value = resp.data.data;
    modalDetalle.value = true;
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al abrir detalle del pedido' });
  }
}

async function aprobarPagoDelPedido() {
  if (!pedidoSeleccionado.value?.pago) return;
  procesandoAccion.value = true;
  try {
    await api.post(`/admin/pagos/${pedidoSeleccionado.value.pago.id}/aprobar`);
    Notify.create({
      type: 'positive',
      message: '¡Pago verificado y aprobado! El pedido pasó a preparación.',
    });
    const resp = await api.get(`/admin/pedidos/${pedidoSeleccionado.value.id}`);
    pedidoSeleccionado.value = resp.data.data;
    await cargarPedidos();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al aprobar el pago',
    });
  } finally {
    procesandoAccion.value = false;
  }
}

function abrirModalRechazoPago() {
  motivoRechazo.value = '';
  modalRechazoPago.value = true;
}

async function rechazarPagoDelPedido() {
  if (!pedidoSeleccionado.value?.pago || !motivoRechazo.value.trim()) return;
  procesandoAccion.value = true;
  try {
    await api.post(`/admin/pagos/${pedidoSeleccionado.value.pago.id}/rechazar`, {
      motivo: motivoRechazo.value.trim(),
    });
    Notify.create({
      type: 'warning',
      message: 'Comprobante rechazado y notificado al cliente.',
    });
    modalRechazoPago.value = false;
    const resp = await api.get(`/admin/pedidos/${pedidoSeleccionado.value.id}`);
    pedidoSeleccionado.value = resp.data.data;
    await cargarPedidos();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al rechazar el pago',
    });
  } finally {
    procesandoAccion.value = false;
  }
}

async function cambiarEstado(nuevoEstado: EstadoPedido) {
  if (!pedidoSeleccionado.value) return;
  procesandoAccion.value = true;
  try {
    const payload: Record<string, any> = { estado: nuevoEstado };
    if (nuevoEstado === 'en_camino' && !pedidoSeleccionado.value.repartidor_ubicacion) {
      payload.lat = Number(pedidoSeleccionado.value.lat) - 0.018;
      payload.lng = Number(pedidoSeleccionado.value.lng) - 0.015;
    }
    const resp = await api.put(`/admin/pedidos/${pedidoSeleccionado.value.id}/estado`, payload);
    pedidoSeleccionado.value = resp.data.data;
    Notify.create({ type: 'positive', message: `Estado actualizado a: ${pedidoSeleccionado.value?.estado_etiqueta || nuevoEstado}` });
    await cargarPedidos();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al cambiar estado',
    });
  } finally {
    procesandoAccion.value = false;
  }
}

async function abrirAsignarRepartidor() {
  try {
    const resp = await api.get('/admin/repartidores');
    repartidores.value = resp.data.data || [];
    modalRepartidores.value = true;
  } catch (e) {
    Notify.create({ type: 'negative', message: 'No se pudo cargar la lista de repartidores' });
  }
}

async function confirmarAsignacion(repartidorId: number) {
  if (!pedidoSeleccionado.value) return;
  try {
    const resp = await api.put(`/admin/pedidos/${pedidoSeleccionado.value.id}/repartidor`, {
      repartidor_id: repartidorId,
    });
    pedidoSeleccionado.value = resp.data.data;
    modalRepartidores.value = false;
    Notify.create({ type: 'positive', message: 'Repartidor asignado con éxito' });
    await cargarPedidos();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al asignar repartidor',
    });
  }
}

function verImagenCompleta(url: string) {
  window.open(url, '_blank');
}

function abrirGoogleMaps(lat: number, lng: number) {
  window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`, '_blank');
}

function abrirWaze(lat: number, lng: number) {
  window.open(`https://waze.com/ul?ll=${lat},${lng}&navigate=yes`, '_blank');
}

function abrirWhatsapp(url?: string) {
  if (url) window.open(url, '_blank');
}

function formatearFecha(f?: string): string {
  if (!f) return '';
  const d = new Date(f);
  return d.toLocaleString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
