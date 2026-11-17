import type { RouteRecordRaw } from 'vue-router';
import clienteRoutes from '../cliente/router/routes';
import negocioRoutes from '../negocio/router/routes';

const target = import.meta.env.APP_TARGET || __APP_TARGET__ || 'cliente';

const routes: RouteRecordRaw[] = target === 'negocio' ? negocioRoutes : clienteRoutes;

export default routes;
