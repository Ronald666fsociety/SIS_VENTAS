import { defineStore } from 'pinia';
import { ref } from 'vue';
import { api } from '../api/client';
import { useAuthStore } from './auth';
import { Notify } from 'quasar';

const STORAGE_KEY = 'tienda_favoritos_ids';

export const useFavoritosStore = defineStore('favoritos', () => {
  let iniciales: number[] = [];
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (Array.isArray(parsed)) {
        iniciales = parsed.map(Number);
      }
    }
  } catch (e) {}

  const ids = ref<number[]>(iniciales);

  function persistir() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(ids.value));
  }

  function esFavorito(productoId: number): boolean {
    return ids.value.includes(productoId);
  }

  async function cargar() {
    const authStore = useAuthStore();
    if (!authStore.isAuthenticated) return;
    try {
      const resp = await api.get('/favoritos');
      if (Array.isArray(resp.data?.ids)) {
        ids.value = resp.data.ids.map(Number);
        persistir();
      }
    } catch (e) {}
  }

  async function toggle(productoId: number) {
    const authStore = useAuthStore();
    const estaba = esFavorito(productoId);
    if (estaba) {
      ids.value = ids.value.filter((id) => id !== productoId);
    } else {
      ids.value.push(productoId);
    }
    persistir();

    if (authStore.isAuthenticated) {
      try {
        const resp = await api.post(`/favoritos/${productoId}`);
        if (Array.isArray(resp.data?.ids)) {
          ids.value = resp.data.ids.map(Number);
          persistir();
        }
      } catch (e) {}
    }

    Notify.create({
      type: estaba ? 'info' : 'positive',
      message: estaba ? 'Quitado de favoritos' : 'Agregado a favoritos',
      timeout: 800,
      position: 'bottom',
    });
  }

  return {
    ids,
    esFavorito,
    cargar,
    toggle,
  };
});
