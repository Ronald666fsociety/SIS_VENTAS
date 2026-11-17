export type Rol = 'cliente' | 'repartidor' | 'operador' | 'admin';

export type EstadoPedido =
  | 'pendiente_pago'
  | 'pago_en_revision'
  | 'pago_verificado'
  | 'preparando'
  | 'listo_para_envio'
  | 'en_camino'
  | 'entregado'
  | 'cancelado'
  | 'entrega_fallida';

export type EstadoPago = 'pendiente' | 'en_revision' | 'verificado' | 'rechazado';

export interface User {
  id: number;
  nombre: string;
  apellido?: string | null;
  email?: string | null;
  telefono: string;
  rol: Rol;
  activo: boolean;
  avatar_url?: string | null;
  created_at?: string;
}

export interface Categoria {
  id: number;
  nombre: string;
  slug: string;
  imagen_url?: string | null;
  orden: number;
  activa: boolean;
  productos_count?: number;
}

export interface ProductoImagen {
  id: number;
  url: string;
  orden: number;
}

export interface Producto {
  id: number;
  categoria_id: number;
  categoria?: { id: number; nombre: string };
  nombre: string;
  descripcion?: string | null;
  sku?: string | null;
  precio: string | number;
  precio_oferta?: string | number | null;
  precio_final: string;
  unidad: string;
  stock: number;
  agotado?: boolean;
  stock_minimo?: number;
  stock_bajo?: boolean;
  activo?: boolean;
  destacado?: boolean;
  imagenes?: ProductoImagen[];
}

export interface ZonaEnvio {
  id: number;
  nombre: string;
  costo_envio: string | number;
  pedido_minimo: string | number;
  centro_lat?: number | null;
  centro_lng?: number | null;
  radio_km?: number | null;
  poligono?: any;
  activa: boolean;
}

export interface Direccion {
  id: number;
  user_id?: number;
  alias: string;
  direccion: string;
  referencia?: string | null;
  lat: number;
  lng: number;
  zona_id?: number | null;
  zona?: ZonaEnvio | null;
  es_principal: boolean;
  created_at?: string;
}

export interface PedidoItem {
  producto_id: number;
  nombre_producto: string;
  precio_unitario: string | number;
  cantidad: number;
  subtotal: string | number;
}

export interface LineaTiempo {
  estado: EstadoPedido;
  etiqueta: string;
  nota?: string | null;
  fecha: string;
}

export interface Pago {
  id: number;
  pedido_id: number;
  metodo: string;
  monto: string | number;
  estado: EstadoPago;
  nro_transaccion?: string | null;
  intentos: number;
  intentos_restantes?: number;
  motivo_rechazo?: string | null;
  verificado_en?: string | null;
  comprobante_url?: string | null;
  tiene_comprobante?: boolean;
  pedido?: Pedido;
  created_at?: string;
}

export interface RepartidorUbicacion {
  lat: number;
  lng: number;
  actualizado_en?: string | null;
  distancia_km: number;
  tiempo_estimado_min: number;
  texto_eta: string;
}

export interface Pedido {
  id: number;
  codigo: string;
  estado: EstadoPedido;
  estado_etiqueta: string;
  direccion_texto: string;
  referencia?: string | null;
  lat: number;
  lng: number;
  repartidor_ubicacion?: RepartidorUbicacion | null;
  zona?: { id: number; nombre: string } | null;
  subtotal: string;
  costo_envio: string;
  descuento: string;
  total: string;
  notas_cliente?: string | null;
  codigo_entrega?: string | null;
  entregado_en?: string | null;
  foto_entrega_url?: string | null;
  cliente?: {
    id: number;
    nombre: string;
    apellido?: string | null;
    telefono: string;
    whatsapp_url: string;
  };
  repartidor?: {
    id: number;
    nombre: string;
    telefono: string;
  } | null;
  items?: PedidoItem[];
  pago?: Pago | null;
  linea_tiempo?: LineaTiempo[];
  whatsapp_soporte?: string | null;
  calificacion?: number | null;
  comentario_calificacion?: string | null;
  calificado_at?: string | null;
  created_at: string;
}

export interface ConfiguracionPago {
  banco?: string | null;
  titular?: string | null;
  nro_cuenta?: string | null;
  qr_imagen_url: string;
  instrucciones?: string | null;
  aceptar_efectivo?: boolean;
  verificacion_minutos?: number;
  max_intentos?: number;
}

export interface DashboardResumen {
  pagos_por_verificar: number;
  pedidos_hoy: number;
  ventas_hoy: string;
  stock_bajo: number;
  por_preparar: number;
  listos_para_envio: number;
  en_camino: number;
}
