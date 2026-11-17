<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** Registro público: siempre crea un cliente (nadie se registra como admin desde la app). */
    public function registro(Request $request): JsonResponse
    {
        if ($request->has('telefono')) {
            $request->merge(['telefono' => preg_replace('/[\s\-]/', '', (string) $request->input('telefono'))]);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['nullable', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'regex:/^\+?[0-9]{5,20}$/', 'unique:users,telefono'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ], [
            'telefono.unique' => 'Este número de teléfono ya está registrado.',
            'telefono.regex' => 'Ingresa un número de teléfono válido (solo dígitos).',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $user = User::create([
            'nombre' => $data['nombre'],
            'apellido' => $data['apellido'] ?? null,
            'telefono' => $data['telefono'],
            'email' => $data['email'] ?? null,
            'password' => $data['password'],
            'rol' => Rol::Cliente,
        ]);

        return response()->json($this->respuestaToken($user, 'cliente', $data['device_name'] ?? null), 201);
    }

    public function login(Request $request): JsonResponse
    {
        if ($request->has('telefono')) {
            $request->merge(['telefono' => preg_replace('/[\s\-]/', '', (string) $request->input('telefono'))]);
        }

        $data = $request->validate([
            'telefono' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
            'app' => ['nullable', 'in:cliente,negocio'],
            'device_name' => ['nullable', 'string', 'max:60'],
        ]);
        $app = $data['app'] ?? 'cliente';

        $user = User::query()->where('telefono', $data['telefono'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['telefono' => 'Teléfono o contraseña incorrectos.']);
        }
        if (! $user->activo) {
            throw ValidationException::withMessages(['telefono' => 'Tu cuenta está desactivada. Contacta al negocio.']);
        }
        // La App Negocio es solo para personal (admin, operador, repartidor).
        if ($app === 'negocio' && ! $user->esPersonal()) {
            throw ValidationException::withMessages([
                'telefono' => 'Esta cuenta no tiene permisos para acceder a la App Negocio.',
            ]);
        }

        return response()->json($this->respuestaToken($user, $app, $data['device_name'] ?? null));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    /** Envía un código de recuperación. La respuesta es siempre igual para no revelar si el teléfono existe. */
    public function recuperar(Request $request): JsonResponse
    {
        if ($request->has('telefono')) {
            $request->merge(['telefono' => preg_replace('/[\s\-]/', '', (string) $request->input('telefono'))]);
        }

        $data = $request->validate(['telefono' => ['required', 'string', 'max:20']]);

        $codigoDev = null;
        $user = User::query()->where('telefono', $data['telefono'])->where('activo', true)->first();
        if ($user) {
            $codigo = (string) random_int(100000, 999999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['telefono' => $user->telefono],
                ['token' => Hash::make($codigo), 'created_at' => now()],
            );
            if (! app()->isProduction()) {
                Log::info("Código de recuperación para {$user->telefono}: {$codigo}");
                $codigoDev = $codigo;
            }
        }

        $resp = ['message' => 'Si el teléfono está registrado, recibirás un código de 6 dígitos.'];
        if ($codigoDev !== null) {
            $resp['codigo_dev'] = $codigoDev;
        }

        return response()->json($resp);
    }

    public function restablecer(Request $request): JsonResponse
    {
        if ($request->has('telefono')) {
            $request->merge(['telefono' => preg_replace('/[\s\-]/', '', (string) $request->input('telefono'))]);
        }

        $data = $request->validate([
            'telefono' => ['required', 'string', 'max:20'],
            'codigo' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
        ]);

        $registro = DB::table('password_reset_tokens')->where('telefono', $data['telefono'])->first();
        $vigente = $registro && now()->diffInMinutes($registro->created_at, true) <= 15;

        if (! $vigente || ! Hash::check($data['codigo'], $registro->token)) {
            throw ValidationException::withMessages(['codigo' => 'El código es incorrecto o venció.']);
        }

        $user = User::query()->where('telefono', $data['telefono'])->firstOrFail();
        $user->update(['password' => $data['password']]);
        $user->tokens()->delete();
        DB::table('password_reset_tokens')->where('telefono', $data['telefono'])->delete();

        return response()->json(['message' => 'Contraseña actualizada. Inicia sesión.']);
    }

    private function respuestaToken(User $user, string $app, ?string $dispositivo): array
    {
        $expira = now()->addMinutes(config("tienda.token_minutos.$app"));
        $token = $user->createToken($app.':'.($dispositivo ?: 'dispositivo'), [$app], $expira);

        return [
            'token' => $token->plainTextToken,
            'expira_en' => $expira->toIso8601String(),
            'usuario' => new UserResource($user),
        ];
    }
}
