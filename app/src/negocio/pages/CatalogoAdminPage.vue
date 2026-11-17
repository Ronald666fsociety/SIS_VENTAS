<template>
  <q-page class="q-pa-md">
    <!-- Cabecera con botones de Categorías y Nuevo Producto -->
    <div class="row justify-between items-center q-mb-md">
      <div>
        <div class="text-h6 text-weight-bolder text-grey-9">Catálogo y Categorías</div>
        <div class="text-caption text-grey-6">Productos, fotos, categorías y stock</div>
      </div>
      <div class="row q-gutter-x-xs">
        <q-btn
          outline
          no-caps
          color="primary"
          icon="category"
          label="Categorías"
          class="text-weight-bold"
          @click="abrirModalCategorias"
        />
        <q-btn
          unelevated
          no-caps
          color="primary"
          icon="add"
          label="Producto"
          class="text-weight-bold"
          @click="abrirNuevoProducto"
        />
      </div>
    </div>

    <!-- Chips de filtro por Categoría + botón rápido Nueva Categoría -->
    <div class="row no-wrap scroll q-gutter-x-xs q-mb-sm no-scrollbar items-center">
      <q-chip
        clickable
        :selected="categoriaFiltro === null"
        color="primary"
        :text-color="categoriaFiltro === null ? 'white' : 'primary'"
        :outline="categoriaFiltro !== null"
        label="Todas"
        @click="filtrarPorCategoria(null)"
      />
      <q-chip
        v-for="cat in opcionesCategorias"
        :key="cat.id"
        clickable
        :selected="categoriaFiltro === cat.id"
        color="primary"
        :text-color="categoriaFiltro === cat.id ? 'white' : 'primary'"
        :outline="categoriaFiltro !== cat.id"
        :label="`${cat.nombre} (${cat.productos_count ?? 0})`"
        @click="filtrarPorCategoria(cat.id)"
      />
      <q-chip
        clickable
        color="teal-7"
        text-color="white"
        icon="add"
        label="Nueva Categoría"
        @click="abrirModalCategorias"
      />
    </div>

    <!-- Buscador -->
    <q-input
      v-model="busqueda"
      outlined
      dense
      rounded
      bg-color="white"
      placeholder="Buscar por nombre o SKU..."
      class="q-mb-md"
      debounce="300"
      @update:model-value="cargarProductos"
      clearable
    >
      <template #prepend>
        <q-icon name="search" />
      </template>
    </q-input>

    <div v-if="cargando" class="column items-center q-pa-xl">
      <q-spinner color="primary" size="48px" />
      <div class="text-caption text-grey-7 q-mt-md">Cargando productos...</div>
    </div>

    <div v-else-if="productos.length === 0" class="column items-center q-pa-xl text-center text-grey-6">
      <q-icon name="inventory_2" size="64px" />
      <div class="text-subtitle1 q-mt-md">No hay productos en esta vista</div>
      <q-btn
        unelevated
        color="primary"
        icon="add"
        label="Agregar Primer Producto"
        class="q-mt-md text-weight-bold"
        @click="abrirNuevoProducto"
      />
    </div>

    <!-- Lista de productos -->
    <div v-else class="column q-gutter-y-sm">
      <q-card
        v-for="p in productos"
        :key="p.id"
        flat
        bordered
        class="card-interactive bg-white"
      >
        <q-card-section class="q-pa-sm row items-center justify-between">
          <!-- Miniatura + Nombre y Precio -->
          <div class="row items-center q-gutter-x-sm col">
            <q-avatar
              rounded
              size="64px"
              class="cursor-pointer"
              style="background: #f8fafc; border: 1px solid rgba(15, 23, 42, 0.06);"
              @click.stop="verImagenesProducto(p)"
            >
              <img
                :src="p.imagenes?.[0]?.url || 'https://placehold.co/100x100/eee/999?text=Sin+Foto'"
                style="object-fit: cover;"
              />
            </q-avatar>
            <div class="col ellipsis cursor-pointer" @click="editarProducto(p)">
              <div class="section-eyebrow">
                {{ p.categoria?.nombre || 'Sin categoría' }}
              </div>
              <div class="text-subtitle2 text-weight-bold text-grey-10 ellipsis">{{ p.nombre }}</div>
              <div class="text-caption text-primary text-weight-bolder tabular-nums">
                Bs {{ p.precio }}
                <span v-if="p.precio_oferta" class="text-strike text-grey-6 q-ml-xs">
                  Bs {{ p.precio_oferta }}
                </span>
                <q-badge v-if="p.precio_oferta" color="red-1" text-color="negative" label="Oferta" class="q-ml-xs text-weight-bold" />
              </div>
            </div>
          </div>

          <!-- Toggle Activo / Inactivo + Editar -->
          <div class="column items-end">
            <q-toggle
              v-model="p.activo"
              dense
              color="positive"
              @update:model-value="toggleActivo(p)"
            />
            <div class="row">
              <q-btn flat dense round icon="add_a_photo" color="primary" size="sm" @click="editarProducto(p)">
                <q-tooltip>Subir/Cambiar Foto</q-tooltip>
              </q-btn>
              <q-btn flat dense round icon="edit" color="grey-7" size="sm" @click="editarProducto(p)" />
              <q-btn flat dense round icon="delete_outline" color="negative" size="sm" @click="eliminarProducto(p)" />
            </div>
          </div>
        </q-card-section>

        <q-separator />

        <!-- Control de stock rápido (+ / -) -->
        <q-card-section class="q-py-xs q-px-sm row justify-between items-center bg-grey-1">
          <span class="text-caption text-weight-medium text-grey-8">
            Stock disponible:
            <b :class="p.stock <= (p.stock_minimo || 0) ? 'text-negative' : 'text-positive'">
              {{ p.stock }} {{ p.unidad || 'unid.' }}
            </b>
          </span>
          <div class="row items-center q-gutter-x-xs">
            <q-btn
              round
              dense
              flat
              size="sm"
              icon="remove"
              color="grey-8"
              :disable="p.stock <= 0"
              @click="modificarStock(p, -1)"
            />
            <q-btn
              outline
              dense
              size="sm"
              label="-5"
              color="grey-7"
              :disable="p.stock < 5"
              @click="modificarStock(p, -5)"
            />
            <q-btn
              outline
              dense
              size="sm"
              label="+5"
              color="primary"
              @click="modificarStock(p, 5)"
            />
            <q-btn
              round
              dense
              unelevated
              size="sm"
              icon="add"
              color="primary"
              @click="modificarStock(p, 1)"
            />
          </div>
        </q-card-section>
      </q-card>
    </div>

    <!-- ===================================================== -->
    <!-- MODAL: CREAR / EDITAR PRODUCTO Y SUBIR FOTOS          -->
    <!-- ===================================================== -->
    <q-dialog v-model="modalProducto" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">
            {{ productoEditando ? 'Editar Producto' : 'Nuevo Producto' }}
          </q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <q-form @submit="guardarProducto" class="q-gutter-y-md bg-white q-pa-md rounded-borders shadow-1">
            <!-- Selector de Categoría + Botón Crear Categoría al instante -->
            <div>
              <div class="row items-center q-col-gutter-xs">
                <div class="col">
                  <q-select
                    v-model="formProducto.categoria_id"
                    :options="opcionesCategorias"
                    option-value="id"
                    option-label="nombre"
                    emit-value
                    map-options
                    outlined
                    dense
                    label="Categoría *"
                    :rules="[val => !!val || 'Selecciona una categoría']"
                  />
                </div>
                <div class="col-auto q-pb-md">
                  <q-btn
                    unelevated
                    color="teal-7"
                    icon="add"
                    label="Nueva"
                    no-caps
                    dense
                    class="q-px-sm"
                    style="height: 40px;"
                    @click="mostrarCrearCatRapida = !mostrarCrearCatRapida"
                  >
                    <q-tooltip>Crear una nueva categoría aquí mismo</q-tooltip>
                  </q-btn>
                </div>
              </div>

              <!-- Mini formulario rápido para crear categoría sin salir del producto -->
              <div v-if="mostrarCrearCatRapida" class="row items-center q-gutter-x-xs bg-teal-1 q-pa-sm rounded-borders q-mb-sm">
                <q-input
                  v-model="nombreCatRapida"
                  outlined
                  dense
                  bg-color="white"
                  placeholder="Nombre de la nueva categoría (ej: Lácteos, Carnes...)"
                  class="col"
                  @keyup.enter="crearCategoriaRapida"
                />
                <q-btn
                  unelevated
                  color="teal-8"
                  label="Crear"
                  no-caps
                  :loading="creandoCat"
                  @click="crearCategoriaRapida"
                />
              </div>
            </div>

            <q-input
              v-model="formProducto.nombre"
              outlined
              dense
              label="Nombre del producto *"
              placeholder="Ej: Coca-Cola 2 Litros Retornable"
              :rules="[val => !!val || 'El nombre es obligatorio']"
            />

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model="formProducto.sku"
                  outlined
                  dense
                  label="Código / SKU (opcional)"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model="formProducto.unidad"
                  outlined
                  dense
                  label="Unidad (ej: unidad, kg, litro)"
                />
              </div>
            </div>

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model.number="formProducto.precio"
                  type="number"
                  step="0.1"
                  outlined
                  dense
                  label="Precio regular (Bs) *"
                  :rules="[val => val >= 0 || 'Inválido']"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model.number="formProducto.precio_oferta"
                  type="number"
                  step="0.1"
                  outlined
                  dense
                  label="Precio oferta (opcional)"
                  hint="Menor al precio regular"
                />
              </div>
            </div>

            <div class="row q-col-gutter-sm">
              <div class="col-6">
                <q-input
                  v-model.number="formProducto.stock"
                  type="number"
                  outlined
                  dense
                  label="Stock disponible *"
                />
              </div>
              <div class="col-6">
                <q-input
                  v-model.number="formProducto.stock_minimo"
                  type="number"
                  outlined
                  dense
                  label="Stock mínimo alerta"
                />
              </div>
            </div>

            <div class="row q-gutter-x-md">
              <q-checkbox v-model="formProducto.destacado" label="Producto Destacado (aparece en filtro ⭐)" />
              <q-checkbox v-model="formProducto.activo" label="Visible para clientes" />
            </div>

            <q-input
              v-model="formProducto.descripcion"
              outlined
              dense
              type="textarea"
              rows="2"
              label="Descripción detallada"
            />

            <!-- SECCIÓN DE FOTOS DEL PRODUCTO -->
            <q-card flat bordered class="bg-grey-1 q-pa-md rounded-borders">
              <div class="text-subtitle2 text-weight-bold text-grey-9 q-mb-xs row items-center">
                <q-icon name="photo_camera" color="primary" size="20px" class="q-mr-xs" />
                Fotografías del Producto
              </div>

              <!-- Fotos actuales si se está editando -->
              <div v-if="productoEditando?.imagenes && productoEditando.imagenes.length > 0" class="q-mb-md">
                <div class="text-caption text-grey-7 q-mb-xs">Fotos actuales:</div>
                <div class="row q-gutter-sm">
                  <div
                    v-for="(img, idx) in productoEditando.imagenes"
                    :key="img.id"
                    class="relative-position"
                  >
                    <img
                      :src="img.url"
                      style="width: 85px; height: 85px; object-fit: cover;"
                      class="rounded-borders shadow-1 bg-white cursor-pointer"
                      @click="verImagenesProducto(productoEditando, idx)"
                    />
                    <q-btn
                      round
                      dense
                      size="xs"
                      color="negative"
                      icon="close"
                      class="absolute-top-right"
                      style="top: -6px; right: -6px;"
                      @click.stop="borrarImagenExistente(img.id)"
                    >
                      <q-tooltip>Eliminar esta foto</q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </div>

              <!-- Selector de nueva foto con vista previa instantánea -->
              <q-file
                v-model="nuevaImagen"
                outlined
                dense
                bg-color="white"
                label="Seleccionar foto desde galería o cámara"
                accept="image/*"
                clearable
                @update:model-value="onNuevaImagenSeleccionada"
              >
                <template #prepend>
                  <q-icon name="add_a_photo" color="primary" />
                </template>
              </q-file>

              <!-- Vista previa de la foto seleccionada -->
              <div v-if="previewImagenUrl" class="q-mt-sm text-center">
                <div class="text-caption text-positive text-weight-bold q-mb-xs">
                  Vista previa de la foto a subir:
                </div>
                <img
                  :src="previewImagenUrl"
                  alt="Preview"
                  class="rounded-borders shadow-2 bg-white"
                  style="max-height: 180px; max-width: 100%; object-fit: contain;"
                />
              </div>
            </q-card>

            <q-btn
              unelevated
              color="primary"
              :label="productoEditando ? 'Guardar Cambios' : 'Crear Producto'"
              icon="save"
              type="submit"
              :loading="guardando"
              class="full-width text-weight-bolder q-mt-md shadow-2"
              style="min-height: 48px; font-size: 16px;"
            />
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- ===================================================== -->
    <!-- MODAL: GESTIÓN COMPLETA DE CATEGORÍAS                 -->
    <!-- ===================================================== -->
    <q-dialog v-model="modalCategorias" maximized transition-show="slide-up" transition-hide="slide-down">
      <q-card class="column bg-grey-1">
        <q-toolbar class="bg-dark text-white">
          <q-toolbar-title class="text-subtitle1 text-weight-bold">
            Gestión de Categorías
          </q-toolbar-title>
          <q-btn flat round dense icon="close" v-close-popup />
        </q-toolbar>

        <q-card-section class="col scroll q-pa-md">
          <!-- Formulario para agregar o editar categoría -->
          <q-card flat bordered class="rounded-borders bg-white shadow-1 q-pa-md q-mb-md">
            <div class="text-subtitle2 text-weight-bold q-mb-sm text-primary">
              {{ categoriaEditando ? `Editando: ${categoriaEditando.nombre}` : 'Agregar Nueva Categoría' }}
            </div>
            <div class="row q-col-gutter-sm items-center">
              <div class="col-12 col-sm-7">
                <q-input
                  v-model="formCategoria.nombre"
                  outlined
                  dense
                  label="Nombre de la categoría *"
                  placeholder="Ej: Lácteos, Panadería, Limpieza, Carnes, Bebidas..."
                  @keyup.enter="guardarCategoria"
                />
              </div>
              <div class="col-6 col-sm-2">
                <q-input
                  v-model.number="formCategoria.orden"
                  type="number"
                  outlined
                  dense
                  label="Orden"
                />
              </div>
              <div class="col-6 col-sm-3 row q-gutter-x-xs">
                <q-btn
                  unelevated
                  color="primary"
                  :label="categoriaEditando ? 'Actualizar' : 'Agregar'"
                  icon="add"
                  :loading="creandoCat"
                  class="col text-weight-bold"
                  @click="guardarCategoria"
                />
                <q-btn
                  v-if="categoriaEditando"
                  flat
                  dense
                  icon="close"
                  color="grey-7"
                  @click="cancelarEdicionCategoria"
                />
              </div>
            </div>
          </q-card>

          <!-- Lista de todas las categorías -->
          <div class="text-subtitle2 text-weight-bold text-grey-8 q-mb-xs">
            Categorías registradas ({{ opcionesCategorias.length }})
          </div>

          <q-card flat bordered class="rounded-borders bg-white shadow-1">
            <q-list separator>
              <q-item v-for="cat in opcionesCategorias" :key="cat.id" class="q-py-sm">
                <q-item-section avatar>
                  <q-avatar color="blue-1" text-color="primary" icon="label" />
                </q-item-section>

                <q-item-section>
                  <q-item-label class="text-weight-bold">{{ cat.nombre }}</q-item-label>
                  <q-item-label caption>
                    {{ cat.productos_count ?? 0 }} producto(s) · Orden: {{ cat.orden }}
                  </q-item-label>
                </q-item-section>

                <q-item-section side>
                  <div class="row items-center q-gutter-x-xs">
                    <q-toggle
                      v-model="cat.activa"
                      color="positive"
                      dense
                      @update:model-value="toggleCategoriaActiva(cat)"
                    >
                      <q-tooltip>Activar / Desactivar categoría</q-tooltip>
                    </q-toggle>
                    <q-btn
                      flat
                      round
                      dense
                      icon="edit"
                      color="primary"
                      size="sm"
                      @click="seleccionarCategoriaParaEditar(cat)"
                    />
                    <q-btn
                      flat
                      round
                      dense
                      icon="delete_outline"
                      color="negative"
                      size="sm"
                      @click="eliminarCategoria(cat)"
                    />
                  </div>
                </q-item-section>
              </q-item>
            </q-list>
          </q-card>
        </q-card-section>
      </q-card>
    </q-dialog>

    <!-- Visor de imagen a pantalla completa -->
    <VisorImagen
      v-model="visorAbierto"
      :imagenes="visorImagenes"
      :indice-inicial="visorIndice"
      :titulo="visorTitulo"
    />
  </q-page>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { api } from '../../shared/api/client';
import { Notify, Dialog } from 'quasar';
import VisorImagen from '../../shared/components/VisorImagen.vue';
import type { Producto, Categoria } from '../../shared/types';

const productos = ref<Producto[]>([]);
const opcionesCategorias = ref<Categoria[]>([]);
const cargando = ref(true);
const busqueda = ref('');
const categoriaFiltro = ref<number | null>(null);

// Estado modal producto
const modalProducto = ref(false);
const productoEditando = ref<Producto | null>(null);
const nuevaImagen = ref<File | null>(null);
const previewImagenUrl = ref<string | null>(null);
const guardando = ref(false);

const visorAbierto = ref(false);
const visorImagenes = ref<string[]>([]);
const visorIndice = ref(0);
const visorTitulo = ref('');

function verImagenesProducto(p: Producto, idx = 0) {
  if (!p.imagenes || p.imagenes.length === 0) {
    editarProducto(p);
    return;
  }
  visorImagenes.value = p.imagenes.map((i) => i.url);
  visorIndice.value = idx;
  visorTitulo.value = p.nombre;
  visorAbierto.value = true;
}

const mostrarCrearCatRapida = ref(false);
const nombreCatRapida = ref('');

const formProducto = ref({
  categoria_id: null as number | null,
  nombre: '',
  sku: '',
  unidad: 'unidad',
  precio: 0,
  precio_oferta: null as number | null,
  stock: 10,
  stock_minimo: 5,
  descripcion: '',
  destacado: false,
  activo: true,
});

// Estado modal categorías
const modalCategorias = ref(false);
const creandoCat = ref(false);
const categoriaEditando = ref<Categoria | null>(null);
const formCategoria = ref({
  nombre: '',
  orden: 1,
  activa: true,
});

onMounted(() => {
  cargarCategorias();
  cargarProductos();
});

async function cargarProductos() {
  cargando.value = true;
  try {
    const params: Record<string, any> = {};
    if (busqueda.value && busqueda.value.trim()) params.q = busqueda.value.trim();
    if (categoriaFiltro.value !== null) params.categoria = categoriaFiltro.value;

    const resp = await api.get('/admin/productos', { params });
    productos.value = resp.data.data || [];
  } catch (e) {
    Notify.create({ type: 'negative', message: 'Error al cargar productos' });
  } finally {
    cargando.value = false;
  }
}

async function cargarCategorias() {
  try {
    const resp = await api.get('/admin/categorias');
    opcionesCategorias.value = resp.data.data || [];
  } catch (e) {}
}

function filtrarPorCategoria(id: number | null) {
  categoriaFiltro.value = categoriaFiltro.value === id ? null : id;
  cargarProductos();
}

function onNuevaImagenSeleccionada(file: File | null) {
  if (file) {
    previewImagenUrl.value = URL.createObjectURL(file);
  } else {
    previewImagenUrl.value = null;
  }
}

async function modificarStock(p: Producto, delta: number) {
  try {
    const resp = await api.patch(`/admin/productos/${p.id}/stock`, { delta });
    p.stock = resp.data.data.stock;
    Notify.create({ type: 'positive', message: `Stock de ${p.nombre}: ${p.stock}`, timeout: 800 });
  } catch (err: any) {
    Notify.create({ type: 'negative', message: err.response?.data?.message || 'Error al cambiar stock' });
  }
}

async function toggleActivo(p: Producto) {
  try {
    await api.put(`/admin/productos/${p.id}`, { activo: p.activo });
  } catch (e) {
    p.activo = !p.activo;
  }
}

function abrirNuevoProducto() {
  productoEditando.value = null;
  formProducto.value = {
    categoria_id: categoriaFiltro.value || opcionesCategorias.value[0]?.id || null,
    nombre: '',
    sku: '',
    unidad: 'unidad',
    precio: 0,
    precio_oferta: null,
    stock: 10,
    stock_minimo: 5,
    descripcion: '',
    destacado: false,
    activo: true,
  };
  nuevaImagen.value = null;
  previewImagenUrl.value = null;
  mostrarCrearCatRapida.value = false;
  modalProducto.value = true;
}

function editarProducto(p: Producto) {
  productoEditando.value = p;
  formProducto.value = {
    categoria_id: p.categoria_id,
    nombre: p.nombre,
    sku: p.sku || '',
    unidad: p.unidad || 'unidad',
    precio: typeof p.precio === 'string' ? parseFloat(p.precio) : p.precio,
    precio_oferta: p.precio_oferta ? parseFloat(String(p.precio_oferta)) : null,
    stock: p.stock,
    stock_minimo: p.stock_minimo ?? 5,
    descripcion: p.descripcion || '',
    destacado: Boolean(p.destacado),
    activo: p.activo !== false,
  };
  nuevaImagen.value = null;
  previewImagenUrl.value = null;
  mostrarCrearCatRapida.value = false;
  modalProducto.value = true;
}

async function borrarImagenExistente(imagenId: number) {
  try {
    await api.delete(`/admin/producto-imagenes/${imagenId}`);
    if (productoEditando.value?.imagenes) {
      productoEditando.value.imagenes = productoEditando.value.imagenes.filter((i) => i.id !== imagenId);
    }
    await cargarProductos();
    Notify.create({ type: 'info', message: 'Foto eliminada' });
  } catch (e) {
    Notify.create({ type: 'negative', message: 'No se pudo eliminar la foto' });
  }
}

async function guardarProducto() {
  guardando.value = true;
  try {
    const formData = new FormData();
    formData.append('categoria_id', String(formProducto.value.categoria_id));
    formData.append('nombre', formProducto.value.nombre.trim());
    formData.append('unidad', formProducto.value.unidad.trim() || 'unidad');
    formData.append('precio', String(formProducto.value.precio));
    formData.append('stock', String(formProducto.value.stock));
    formData.append('stock_minimo', String(formProducto.value.stock_minimo));
    formData.append('destacado', formProducto.value.destacado ? '1' : '0');
    formData.append('activo', formProducto.value.activo ? '1' : '0');

    if (formProducto.value.sku.trim()) {
      formData.append('sku', formProducto.value.sku.trim());
    }
    if (formProducto.value.descripcion.trim()) {
      formData.append('descripcion', formProducto.value.descripcion.trim());
    }
    if (
      formProducto.value.precio_oferta !== null &&
      formProducto.value.precio_oferta !== undefined &&
      String(formProducto.value.precio_oferta) !== '' &&
      Number(formProducto.value.precio_oferta) > 0
    ) {
      formData.append('precio_oferta', String(formProducto.value.precio_oferta));
    }

    if (nuevaImagen.value) {
      formData.append('imagenes[]', nuevaImagen.value);
    }

    if (productoEditando.value) {
      await api.post(`/admin/productos/${productoEditando.value.id}`, formData);
      Notify.create({ type: 'positive', message: 'Producto actualizado correctamente' });
    } else {
      await api.post('/admin/productos', formData);
      Notify.create({ type: 'positive', message: 'Producto creado con éxito' });
    }

    modalProducto.value = false;
    await Promise.all([cargarProductos(), cargarCategorias()]);
  } catch (err: any) {
    const errObj = err.response?.data?.errors;
    const firstErr = errObj ? Object.values(errObj)[0] : null;
    const msg = (Array.isArray(firstErr) ? firstErr[0] : firstErr) ||
      err.response?.data?.message ||
      'Error al guardar producto';
    Notify.create({ type: 'negative', message: msg });
  } finally {
    guardando.value = false;
  }
}

function eliminarProducto(p: Producto) {
  Dialog.create({
    title: 'Eliminar producto',
    message: `¿Estás seguro de eliminar "${p.nombre}" del catálogo?`,
    cancel: true,
    ok: { label: 'Eliminar', color: 'negative' },
  }).onOk(async () => {
    try {
      await api.delete(`/admin/productos/${p.id}`);
      Notify.create({ type: 'info', message: 'Producto eliminado' });
      await Promise.all([cargarProductos(), cargarCategorias()]);
    } catch (err: any) {
      Notify.create({ type: 'negative', message: err.response?.data?.message || 'No se pudo eliminar' });
    }
  });
}

// ---------- FUNCIONES DE CATEGORÍAS ----------
function abrirModalCategorias() {
  cancelarEdicionCategoria();
  modalCategorias.value = true;
}

function seleccionarCategoriaParaEditar(cat: Categoria) {
  categoriaEditando.value = cat;
  formCategoria.value = {
    nombre: cat.nombre,
    orden: cat.orden || 1,
    activa: cat.activa,
  };
}

function cancelarEdicionCategoria() {
  categoriaEditando.value = null;
  formCategoria.value = {
    nombre: '',
    orden: opcionesCategorias.value.length + 1,
    activa: true,
  };
}

async function crearCategoriaRapida() {
  if (!nombreCatRapida.value.trim()) return;
  creandoCat.value = true;
  try {
    const resp = await api.post('/admin/categorias', {
      nombre: nombreCatRapida.value.trim(),
      orden: opcionesCategorias.value.length + 1,
      activa: true,
    });
    const nueva: Categoria = resp.data;
    await cargarCategorias();
    formProducto.value.categoria_id = nueva.id;
    nombreCatRapida.value = '';
    mostrarCrearCatRapida.value = false;
    Notify.create({ type: 'positive', message: `Categoría "${nueva.nombre}" creada y seleccionada` });
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al crear categoría',
    });
  } finally {
    creandoCat.value = false;
  }
}

async function guardarCategoria() {
  if (!formCategoria.value.nombre.trim()) {
    Notify.create({ type: 'warning', message: 'Escribe el nombre de la categoría' });
    return;
  }
  creandoCat.value = true;
  try {
    if (categoriaEditando.value) {
      await api.put(`/admin/categorias/${categoriaEditando.value.id}`, {
        nombre: formCategoria.value.nombre.trim(),
        orden: formCategoria.value.orden,
        activa: formCategoria.value.activa,
      });
      Notify.create({ type: 'positive', message: 'Categoría actualizada' });
    } else {
      await api.post('/admin/categorias', {
        nombre: formCategoria.value.nombre.trim(),
        orden: formCategoria.value.orden,
        activa: formCategoria.value.activa,
      });
      Notify.create({ type: 'positive', message: 'Categoría creada con éxito' });
    }
    await cargarCategorias();
    cancelarEdicionCategoria();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'Error al guardar categoría',
    });
  } finally {
    creandoCat.value = false;
  }
}

async function toggleCategoriaActiva(cat: Categoria) {
  try {
    await api.put(`/admin/categorias/${cat.id}`, { activa: cat.activa });
    Notify.create({
      type: 'info',
      message: `Categoría ${cat.activa ? 'activada' : 'desactivada'}`,
      timeout: 900,
    });
  } catch (e) {
    cat.activa = !cat.activa;
  }
}

async function eliminarCategoria(cat: Categoria) {
  try {
    await api.delete(`/admin/categorias/${cat.id}`);
    Notify.create({ type: 'info', message: 'Categoría eliminada' });
    if (categoriaFiltro.value === cat.id) {
      categoriaFiltro.value = null;
      await cargarProductos();
    }
    await cargarCategorias();
  } catch (err: any) {
    Notify.create({
      type: 'negative',
      message: err.response?.data?.message || 'No se puede eliminar una categoría con productos',
    });
  }
}
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
