export interface CiudadBolivia {
  nombre: string;
  lat: number;
  lng: number;
  radioSugeridoKm?: number;
}

export const CIUDADES_BOLIVIA: CiudadBolivia[] = [
  { nombre: 'Santa Cruz de la Sierra', lat: -17.7833, lng: -63.1821, radioSugeridoKm: 25 },
  { nombre: 'La Paz', lat: -16.4897, lng: -68.1193, radioSugeridoKm: 20 },
  { nombre: 'El Alto', lat: -16.5100, lng: -68.1600, radioSugeridoKm: 20 },
  { nombre: 'Cochabamba', lat: -17.3935, lng: -66.1570, radioSugeridoKm: 25 },
  { nombre: 'Sucre (Chuquisaca)', lat: -19.0196, lng: -65.2620, radioSugeridoKm: 15 },
  { nombre: 'Tarija', lat: -21.5355, lng: -64.7296, radioSugeridoKm: 15 },
  { nombre: 'Oruro', lat: -17.9833, lng: -67.1500, radioSugeridoKm: 15 },
  { nombre: 'Potosí', lat: -19.5836, lng: -65.7531, radioSugeridoKm: 15 },
  { nombre: 'Trinidad (Beni)', lat: -14.8333, lng: -64.9000, radioSugeridoKm: 15 },
  { nombre: 'Cobija (Pando)', lat: -11.0267, lng: -68.7692, radioSugeridoKm: 15 },
];

export const CENTRO_BOLIVIA = {
  nombre: 'Toda Bolivia (Nacional)',
  lat: -17.3935,
  lng: -66.1570,
  radioKm: 1200,
};
