import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('../layouts/ClienteLayout.vue'),
    children: [
      { path: '', redirect: '/inicio' },
      { path: 'inicio', component: () => import('../pages/InicioPage.vue') },
      { path: 'carrito', component: () => import('../pages/CarritoPage.vue') },
      { path: 'checkout', component: () => import('../pages/CheckoutPage.vue') },
      { path: 'pedidos', component: () => import('../pages/MisPedidosPage.vue') },
      { path: 'pedido/:id', component: () => import('../pages/SeguimientoPage.vue') },
      { path: 'pedido/:id/pago', component: () => import('../pages/PagoQRPage.vue') },
      { path: 'producto/:id', component: () => import('../pages/ProductoDetallePage.vue') },
      { path: 'perfil', component: () => import('../pages/PerfilPage.vue') },
    ],
  },
  {
    path: '/login',
    component: () => import('../pages/LoginPage.vue'),
  },
  {
    path: '/registro',
    component: () => import('../pages/RegistroPage.vue'),
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('../../pages/ErrorNotFound.vue'),
  },
];

export default routes;
