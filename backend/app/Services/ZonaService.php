<?php

namespace App\Services;

use App\Models\ZonaEnvio;

class ZonaService
{
    /**
     * Devuelve la zona activa que cubre el punto, o null.
     * Prioriza polígono GeoJSON si existe; si no, centro + radio.
     * Si varias zonas cubren el punto, gana la de menor radio (más específica).
     */
    public function resolver(float $lat, float $lng): ?ZonaEnvio
    {
        $candidatas = ZonaEnvio::query()->where('activa', true)->get()->filter(
            fn (ZonaEnvio $z) => $this->cubre($z, $lat, $lng)
        );

        return $candidatas->sortBy(fn (ZonaEnvio $z) => $z->radio_km ?? PHP_INT_MAX)->first();
    }

    public function cubre(ZonaEnvio $zona, float $lat, float $lng): bool
    {
        if (! empty($zona->poligono)) {
            $anillo = $this->anilloExterior($zona->poligono);
            if ($anillo !== null) {
                return $this->puntoEnPoligono($lat, $lng, $anillo);
            }
        }

        if ($zona->centro_lat !== null && $zona->centro_lng !== null && $zona->radio_km !== null) {
            return $this->distanciaKm($lat, $lng, $zona->centro_lat, $zona->centro_lng) <= $zona->radio_km;
        }

        return false;
    }

    public function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** GeoJSON usa [lng, lat]. Acepta Feature, Polygon o un anillo directo. */
    private function anilloExterior(array $geo): ?array
    {
        if (($geo['type'] ?? null) === 'Feature') {
            $geo = $geo['geometry'] ?? [];
        }
        if (($geo['type'] ?? null) === 'Polygon') {
            return $geo['coordinates'][0] ?? null;
        }

        return null;
    }

    private function puntoEnPoligono(float $lat, float $lng, array $anillo): bool
    {
        $dentro = false;
        $n = count($anillo);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $anillo[$i];
            [$xj, $yj] = $anillo[$j];
            if ((($yi > $lat) !== ($yj > $lat)) && ($lng < ($xj - $xi) * ($lat - $yi) / (($yj - $yi) ?: 1e-12) + $xi)) {
                $dentro = ! $dentro;
            }
        }

        return $dentro;
    }
}
