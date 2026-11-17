<template>
  <div class="q-mb-sm">
    <q-card
      flat
      bordered
      class="bg-white cursor-pointer card-interactive q-px-md q-py-xs"
      @click="abrirMapa"
    >
      <div class="row items-center justify-between no-wrap">
        <div class="row items-center q-gutter-x-sm ellipsis">
          <q-icon
            :name="coords ? 'my_location' : 'location_searching'"
            :color="coords ? 'positive' : 'primary'"
            size="18px"
          />
          <div class="ellipsis">
            <span class="text-caption text-weight-bold text-grey-9">
              {{ coords ? 'Ubicación actual activa' : (cargando ? 'Localizando GPS...' : 'Ver mi ubicación actual') }}
            </span>
            <span v-if="coords" class="text-caption text-grey-6 q-ml-xs tabular-nums">
              ({{ coords.lat.toFixed(4) }}, {{ coords.lng.toFixed(4) }})
            </span>
          </div>
        </div>
        <div class="row items-center q-gutter-x-xs">
          <q-btn
            flat
            round
            dense
            size="xs"
            icon="refresh"
            color="primary"
            :loading="cargando"
            @click.stop="actualizarGps(false)"
          >
            <q-tooltip>Actualizar GPS</q-tooltip>
          </q-btn>
          <q-icon name="map" color="primary" size="18px" />
        </div>
      </div>
    </q-card>

    <q-dialog v-model="modalAbierto" @show="inicializarMapa">
      <q-card class="rounded-xl q-pa-md" style="min-width: 330px; width: 92vw; max-width: 520px;">
        <div class="row items-center justify-between q-mb-sm">
          <div class="row items-center q-gutter-x-xs">
            <q-icon name="my_location" color="positive" size="22px" />
            <span class="text-subtitle1 text-weight-bolder text-grey-10">Mi Ubicación Actual (GPS)</span>
          </div>
          <q-btn flat round dense icon="close" v-close-popup />
        </div>

        <div
          ref="mapaEl"
          class="rounded-borders shadow-1"
          style="height: 290px; width: 100%; overflow: hidden;"
        ></div>

        <div class="row items-center justify-between q-mt-sm">
          <div class="text-caption text-grey-7 tabular-nums">
            <span v-if="coords">Lat: <b>{{ coords.lat.toFixed(6) }}</b> · Lng: <b>{{ coords.lng.toFixed(6) }}</b></span>
            <span v-else>Activa el GPS de tu celular para mostrar tu posición.</span>
          </div>
          <q-btn
            unelevated
            dense
            no-caps
            color="primary"
            icon="gps_fixed"
            label="Centrar en mí"
            :loading="cargando"
            class="q-px-sm"
            @click="actualizarGps(false)"
          />
        </div>
      </q-card>
    </q-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Notify } from 'quasar';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { obtenerGpsActual, type CoordenadasGps } from '../utils/ubicacion';

const coords = ref<CoordenadasGps | null>(null);
const cargando = ref(false);
const modalAbierto = ref(false);
const mapaEl = ref<HTMLElement | null>(null);

let map: L.Map | null = null;
let marker: L.Marker | null = null;
let circle: L.Circle | null = null;

const pinIcon = L.icon({
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
});

onMounted(() => {
  actualizarGps(true);
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

async function actualizarGps(silencioso = false) {
  cargando.value = true;
  try {
    const pos = await obtenerGpsActual(12000);
    if (pos) {
      coords.value = pos;
      actualizarMarcador();
      if (!silencioso) {
        Notify.create({ type: 'positive', message: 'Ubicación actual actualizada', timeout: 1200 });
      }
    } else if (!silencioso) {
      Notify.create({
        type: 'warning',
        message: 'No se pudo obtener tu GPS. Activa la ubicación del teléfono y otorga permisos.',
      });
    }
  } finally {
    cargando.value = false;
  }
}

async function abrirMapa() {
  modalAbierto.value = true;
  if (!coords.value) {
    await actualizarGps(false);
  }
}

async function inicializarMapa() {
  await nextTick();
  if (!mapaEl.value) return;
  if (map) {
    map.remove();
    map = null;
    marker = null;
    circle = null;
  }
  const lat = coords.value?.lat ?? -17.7833;
  const lng = coords.value?.lng ?? -63.1821;
  map = L.map(mapaEl.value).setView([lat, lng], coords.value ? 16 : 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
  }).addTo(map);
  actualizarMarcador();
}

function actualizarMarcador() {
  if (!map || !coords.value) return;
  const { lat, lng, precision } = coords.value;
  map.setView([lat, lng], 16);
  if (marker) {
    marker.setLatLng([lat, lng]);
  } else {
    marker = L.marker([lat, lng], { icon: pinIcon }).addTo(map).bindPopup('Tu ubicación actual').openPopup();
  }
  const radio = Math.min(precision || 40, 200);
  if (circle) {
    circle.setLatLng([lat, lng]).setRadius(radio);
  } else {
    circle = L.circle([lat, lng], {
      radius: radio,
      color: '#4f46e5',
      fillColor: '#6366f1',
      fillOpacity: 0.16,
      weight: 1.5,
    }).addTo(map);
  }
}
</script>
