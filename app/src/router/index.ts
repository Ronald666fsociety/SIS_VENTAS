import { defineRouter } from '#q-app';
import {
  createMemoryHistory,
  createRouter,
  createWebHashHistory,
  createWebHistory,
} from 'vue-router';

import routes from './routes';

export default defineRouter((/* { store, ssrContext } */) => {
  const createHistory = import.meta.env.QUASAR_SERVER
    ? createMemoryHistory
    : (import.meta.env.QUASAR_VUE_ROUTER_MODE === 'history' ? createWebHistory : createWebHashHistory);

  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createHistory(import.meta.env.QUASAR_VUE_ROUTER_BASE)
  });

  Router.beforeEach((to, from, next) => {
    const target = import.meta.env.APP_TARGET || __APP_TARGET__ || 'cliente';

    if (target === 'negocio') {
      const token = localStorage.getItem('auth_token_negocio');
      let userRol = '';
      try {
        const raw = localStorage.getItem('auth_user_negocio');
        if (raw) userRol = JSON.parse(raw)?.rol || '';
      } catch (e) {}

      if (!token && to.path !== '/login') {
        return next('/login');
      }
      if (token && to.path === '/login') {
        return next(userRol === 'repartidor' ? '/negocio/repartidor' : '/negocio/dashboard');
      }
      if (token && userRol === 'repartidor' && to.path.includes('/dashboard')) {
        return next('/negocio/repartidor');
      }
    }
    next();
  });

  return Router;
});
