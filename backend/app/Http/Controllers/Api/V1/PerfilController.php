<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EstadoPedido;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function update(Request $request): UserResource
    {
        $user = $request->user();
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'apellido' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'telefono' => ['sometimes', 'string', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique('users', 'telefono')->ignore($user->id)],
            'password_actual' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'string', 'min:8', 'max:100'],
        ]);

        if (isset($data['password']) && ! Hash::check($data['password_actual'], $user->password)) {
            throw ValidationException::withMessages(['password_actual' => 'La contraseña actual no es correcta.']);
        }
        unset($data['password_actual']);

        // Cambiar el teléfono invalida su verificación.
        if (isset($data['telefono']) && $data['telefono'] !== $user->telefono) {
            $data['telefono_verified_at'] = null;
        }

        $user->forceFill($data)->save();

        return new UserResource($user->fresh());
    }

    /** Eliminación de cuenta (requisito de Apple/Google): anonimiza y conserva el historial de pedidos. */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'La contraseña no es correcta.']);
        }

        $abiertos = $user->pedidos()
            ->whereNotIn('estado', [EstadoPedido::Entregado->value, EstadoPedido::Cancelado->value])
            ->exists();
        if ($abiertos) {
            throw ValidationException::withMessages(['password' => 'Tienes pedidos en curso. Podrás eliminar la cuenta cuando terminen.']);
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();
            $user->dispositivos()->delete();
            $user->direcciones()->delete();
            $user->forceFill([
                'nombre' => 'Cuenta eliminada',
                'apellido' => null,
                'email' => null,
                'telefono' => 'del'.$user->id.'_'.dechex(time()),
                'avatar_url' => null,
                'activo' => false,
                'password' => Str::random(40),
            ])->save();
            $user->delete();
        });

        return response()->json(['message' => 'Tu cuenta fue eliminada.']);
    }

    public function gastos(Request $request): JsonResponse
    {
        $vendidos = array_map(fn ($e) => $e->value, EstadoPedido::vendidos());
        $base = $request->user()->pedidos()->whereIn('estado', $vendidos);

        $totalGastado = (float) (clone $base)->sum('total');
        $totalMes = (float) (clone $base)->where('created_at', '>=', now()->startOfMonth())->sum('total');
        $cantidad = (int) (clone $base)->count();
        $promedio = $cantidad > 0 ? $totalGastado / $cantidad : 0;

        $ultimos = (clone $base)->with('pago')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'total' => $p->total,
                'estado' => $p->estado->value,
                'estado_etiqueta' => $p->estado->etiqueta(),
                'metodo_pago' => $p->pago?->metodo ?? 'qr',
                'calificacion' => $p->calificacion,
                'fecha' => $p->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'total_gastado' => number_format($totalGastado, 2, '.', ''),
            'total_mes' => number_format($totalMes, 2, '.', ''),
            'pedidos_completados' => $cantidad,
            'gasto_promedio' => number_format($promedio, 2, '.', ''),
            'ultimos_pedidos' => $ultimos,
        ]);
    }
}
