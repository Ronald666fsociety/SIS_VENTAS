import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: '/login',
  },
  {
    path: '/negocio',
    component: () => import('../layouts/NegocioLayout.vue'),
    children: [
      { path: '', redirect: '/negocio/dashboard' },
      { path: 'dashboard', component: () => import('../pages/DashboardPage.vue') },
      { path: 'pagos', component: () => import('../pages/PagosPage.vue') },
      { path: 'pedidos', component: () => import('../pages/PedidosAdminPage.vue') },
      { path: 'catalogo', component: () => import('../pages/CatalogoAdminPage.vue') },
      { path: 'repartidor', component: () => import('../pages/RepartidorPage.vue') },
      { path: 'repartidor/historial', component: () => import('../pages/RepartidorPage.vue') },
      { path: 'perfil', component: () => import('../../cliente/pages/PerfilPage.vue') },
      { path: 'mas', component: () => import('../pages/MasPage.vue') },
      { path: 'reportes', redirect: '/negocio/mas?seccion=reportes' },
    ],
  },
  {
    path: '/login',
    component: () => import('../pages/LoginNegocioPage.vue'),
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('../../pages/ErrorNotFound.vue'),
  },
];

export default routes;
