<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exports\ArrayExport;
use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use App\Services\ReporteService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    public function ventas(Request $request, ReporteService $reportes, AuditoriaService $auditoria)
    {
        $data = $request->validate([
            'desde' => ['required', 'date'],
            'hasta' => ['required', 'date', 'after_or_equal:desde'],
            'formato' => ['nullable', 'in:json,pdf,xlsx'],
        ]);
        $desde = Carbon::parse($data['desde']);
        $hasta = Carbon::parse($data['hasta']);

        if ($desde->diffInDays($hasta) > 366) {
            abort(422, 'El rango máximo es de un año.');
        }

        $r = $reportes->ventas($desde, $hasta);
        $formato = $data['formato'] ?? 'json';
        $nombre = "ventas_{$r['desde']}_{$r['hasta']}";

        if ($formato !== 'json') {
            $auditoria->registrar($request->user(), 'reporte.exportado', 'reporte', null, ['formato' => $formato, 'desde' => $r['desde'], 'hasta' => $r['hasta']]);
        }

        return match ($formato) {
            'pdf' => Pdf::loadView('reportes.ventas', ['r' => $r])->download("$nombre.pdf"),
            'xlsx' => Excel::download(new ArrayExport($reportes->filasExcel($r)), "$nombre.xlsx"),
            default => response()->json($r),
        };
    }
}
