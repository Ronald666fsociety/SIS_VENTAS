<template>
  <div class="mapa-picker-container column q-gutter-sm">
    <div class="row items-center justify-between q-mb-xs">
      <div class="text-subtitle2 text-weight-bold text-grey-9">
        Ubicación de entrega en Bolivia
      </div>
      <q-btn
        flat
        dense
        color="primary"
        icon="my_location"
        label="Mi ubicación"
        :loading="localizando"
        @click="obtenerUbicacionActual(false)"
      />
    </div>

    <!-- Selector rápido de ciudad/departamento en Bolivia -->
    <q-select
      v-model="ciudadSeleccionada"
      :options="CIUDADES_BOLIVIA"
      option-label="nombre"
      outlined
      dense
      bg-color="white"
      label="Saltar a ciudad / departamento en Bolivia"
      clearable
      @update:model-value="irACiudad"
    >
      <template #prepend>
        <q-icon name="public" color="primary" />
      </template>
    </q-select>

    <!-- Contenedor del mapa -->
    <div ref="mapElement" class="mapa-box rounded-borders shadow-1" style="height: 250px; width: 100%;"></div>

    <div v-if="gpsActualTexto" class="text-caption text-positive text-weight-medium row items-center q-gutter-x-xs">
      <q-icon name="gps_fixed" size="15px" />
      <span>{{ gpsActualTexto }}</span>
    </div>

    <div class="text-caption text-grey-6">
      Toca cualquier punto del mapa o arrastra el marcador azul hasta tu dirección exacta.
    </div>

    <!-- Cartel de validación de cobertura -->
    <q-banner
      v-if="resultadoZona"
      :class="resultadoZona.cubre ? 'bg-positive text-white' : 'bg-negative text-white'"
      rounded
      dense
      class="q-mt-xs"
    >
      <template #avatar>
        <q-icon :name="resultadoZona.cubre ? 'check_circle' : 'error'" color="white" />
      </template>
      <div class="text-weight-medium">
        {{ resultadoZona.mensaje }}
      </div>
      <div v-if="resultadoZona.cubre && resultadoZona.zona" class="text-caption">
        Zona: {{ resultadoZona.zona.nombre }} · Pedido mínimo: Bs {{ resultadoZona.pedido_minimo || 0 }}
      </div>
    </q-banner>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { api } from '../api/client';
import { Notify } from 'quasar';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { CIUDADES_BOLIVIA, type CiudadBolivia } from '../utils/bolivia';
import { obtenerGpsActual } from '../utils/ubicacion';

const props = withDefaults(
  defineProps<{
    initialLat?: number;
    initialLng?: number;
  }>(),
  {
    initialLat: -17.7833,
    initialLng: -63.1821,
  }
);

const emit = defineEmits<{
  (e: 'ubicacion-cambiada', payload: {
    lat: number;
    lng: number;
    zona: any | null;
    costoEnvio: number;
    cubre: boolean;
  }): void;
}>();

const mapElement = ref<HTMLElement | null>(null);
const localizando = ref(false);
const ciudadSeleccionada = ref<CiudadBolivia | null>(null);
const gpsActualTexto = ref('');
const resultadoZona = ref<{
  cubre: boolean;
  zona: any | null;
  costo_envio?: number;
  pedido_minimo?: number;
  mensaje: string;
} | null>(null);

let map: L.Map | null = null;
let marker: L.Marker | null = null;
let precisionCircle: L.Circle | null = null;

// Fix standard Leaflet marker icons in bundlers
const customIcon = L.icon({
  iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
  iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
  shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
  iconSize: [25, 41],
  iconAnchor: [12, 41],
  popupAnchor: [1, -34],
  shadowSize: [41, 41],
});

onMounted(() => {
  if (!mapElement.value) return;

  const lat = props.initialLat;
  const lng = props.initialLng;

  map = L.map(mapElement.value).setView([lat, lng], 14);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
  }).addTo(map);

  marker = L.marker([lat, lng], { draggable: true, icon: customIcon }).addTo(map);

  marker.on('dragend', () => {
    if (!marker) return;
    const pos = marker.getLatLng();
    validarZona(pos.lat, pos.lng);
  });

  map.on('click', (e: L.LeafletMouseEvent) => {
    if (!marker) return;
    marker.setLatLng(e.latlng);
    validarZona(e.latlng.lat, e.latlng.lng);
  });

  // Validar inicialmente y centrar automáticamente en GPS real si está disponible
  validarZona(lat, lng);
  obtenerUbicacionActual(true);
});

onBeforeUnmount(() => {
  if (map) {
    map.remove();
    map = null;
  }
});

function irACiudad(ciudad: CiudadBolivia | null) {
  if (!ciudad || !map || !marker) return;
  map.setView([ciudad.lat, ciudad.lng], 13);
  marker.setLatLng([ciudad.lat, ciudad.lng]);
  validarZona(ciudad.lat, ciudad.lng);
}

async function validarZona(lat: number, lng: number) {
  try {
    const resp = await api.post('/zonas/validar', { lat, lng });
    resultadoZona.value = resp.data;

    emit('ubicacion-cambiada', {
      lat,
      lng,
      zona: resp.data.zona,
      costoEnvio: parseFloat(resp.data.costo_envio || 0),
      cubre: resp.data.cubre,
    });
  } catch (err) {
    console.error('Error al validar zona', err);
  }
}

async function obtenerUbicacionActual(silencioso = false) {
  localizando.value = true;
  try {
    const coords = await obtenerGpsActual(12000);
    if (coords && map && marker) {
      map.setView([coords.lat, coords.lng], 16);
      marker.setLatLng([coords.lat, coords.lng]).bindPopup('Tu ubicación actual').openPopup();

      if (precisionCircle) {
        precisionCircle.setLatLng([coords.lat, coords.lng]).setRadius(Math.min(coords.precision || 40, 200));
      } else {
        precisionCircle = L.circle([coords.lat, coords.lng], {
          radius: Math.min(coords.precision || 40, 200),
          color: '#4f46e5',
          fillColor: '#6366f1',
          fillOpacity: 0.16,
          weight: 1.5,
        }).addTo(map);
      }

      gpsActualTexto.value = `GPS activo: ${coords.lat.toFixed(5)}, ${coords.lng.toFixed(5)}`;
      await validarZona(coords.lat, coords.lng);
    } else if (!silencioso) {
      Notify.create({
        type: 'warning',
        message: 'Activa el GPS de tu celular y acepta el permiso de ubicación.',
      });
    }
  } finally {
    localizando.value = false;
  }
}
</script>

<style scoped>
.mapa-box {
  border: 1px solid #ddd;
  overflow: hidden;
}
</style>
