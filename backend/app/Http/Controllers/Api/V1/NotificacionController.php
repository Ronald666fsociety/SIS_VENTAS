<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Notificacion::query()->where('user_id', $request->user()->id);
        $sinLeer = (clone $q)->where('leida', false)->count();

        return response()->json(
            $q->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->toArray() + ['sin_leer' => $sinLeer]
        );
    }

    public function marcarLeida(Request $request, int $id): JsonResponse
    {
        Notificacion::query()->where('user_id', $request->user()->id)->findOrFail($id)->update(['leida' => true]);

        return response()->json(['message' => 'Marcada como leída.']);
    }

    public function marcarTodas(Request $request): JsonResponse
    {
        Notificacion::query()->where('user_id', $request->user()->id)->where('leida', false)->update(['leida' => true]);

        return response()->json(['message' => 'Todas marcadas como leídas.']);
    }
}
