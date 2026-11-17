import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '../api/client';
import type { Categoria, Producto } from '../types';

export const useCatalogoStore = defineStore('catalogo', () => {
  const categorias = ref<Categoria[]>([]);
  const productos = ref<Producto[]>([]);
  const categoriaActiva = ref<number | null>(null);
  const busqueda = ref('');
  const soloOfertas = ref(false);
  const soloDestacados = ref(false);
  const cargando = ref(false);
  const error = ref<string | null>(null);

  async function cargarCategorias() {
    try {
      const resp = await api.get('/categorias');
      const list = resp.data?.data || resp.data;
      categorias.value = Array.isArray(list) ? list : [];
    } catch (e: any) {
      console.error('Error al cargar categorias', e);
      categorias.value = [];
    }
  }

  async function cargarProductos() {
    cargando.value = true;
    error.value = null;
    try {
      const params: Record<string, any> = {};
      if (categoriaActiva.value) params.categoria = categoriaActiva.value;
      if (busqueda.value.trim()) params.q = busqueda.value.trim();
      if (soloOfertas.value) params.oferta = 1;
      if (soloDestacados.value) params.destacado = 1;

      const resp = await api.get('/productos', { params });
      const list = resp.data?.data || resp.data;
      productos.value = Array.isArray(list) ? list : [];
    } catch (e: any) {
      error.value = e.response?.data?.message || 'Error al cargar productos';
      productos.value = [];
    } finally {
      cargando.value = false;
    }
  }

  function seleccionarCategoria(id: number | null) {
    categoriaActiva.value = categoriaActiva.value === id ? null : id;
    cargarProductos();
  }

  return {
    categorias,
    productos,
    categoriaActiva,
    busqueda,
    soloOfertas,
    soloDestacados,
    cargando,
    error,
    cargarCategorias,
    cargarProductos,
    seleccionarCategoria,
  };
});
