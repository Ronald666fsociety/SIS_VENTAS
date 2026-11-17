<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class UsuarioAdminController extends Controller
{
    public function __construct(private AuditoriaService $auditoria)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['rol' => ['nullable', Rule::enum(Rol::class)], 'q' => ['nullable', 'string', 'max:50']]);

        $q = User::query();
        $q->when($request->filled('rol'), fn ($x) => $x->where('rol', $request->input('rol')));
        $q->when($request->filled('activo'), fn ($x) => $x->where('activo', $request->boolean('activo')));
        $q->when($request->filled('q'), function ($x) use ($request) {
            $t = '%'.str_replace(['%', '_'], ['\%', '\_'], $request->input('q')).'%';
            $x->where(fn ($w) => $w->where('nombre', 'like', $t)->orWhere('apellido', 'like', $t)->orWhere('telefono', 'like', $t));
        });

        return UserResource::collection($q->orderBy('nombre')->paginate(30)->withQueryString());
    }

    /** El admin crea repartidores, operadores u otros admins. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['nullable', 'string', 'max:100'],
            'telefono' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/', 'unique:users,telefono'],
            'email' => ['nullable', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'rol' => ['required', Rule::enum(Rol::class)],
        ]);

        $user = User::create($data);
        $this->auditoria->registrar($request->user(), 'usuario.creado', 'usuario', $user->id, ['rol' => $user->rol->value]);

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $usuario): UserResource
    {
        return new UserResource($usuario);
    }

    public function update(Request $request, User $usuario): UserResource
    {
        $data = $request->validate([
            'nombre' => ['sometimes', 'string', 'max:100'],
            'apellido' => ['nullable', 'string', 'max:100'],
            'telefono' => ['sometimes', 'string', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique('users', 'telefono')->ignore($usuario->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password' => ['sometimes', 'string', 'min:8', 'max:100'],
            'rol' => ['sometimes', Rule::enum(Rol::class)],
            'activo' => ['sometimes', 'boolean'],
        ]);

        if ($usuario->id === $request->user()->id && (
            (isset($data['activo']) && ! $data['activo']) || (isset($data['rol']) && $data['rol'] !== $usuario->rol->value)
        )) {
            throw ValidationException::withMessages(['usuario' => 'No puedes desactivarte ni cambiar tu propio rol.']);
        }

        $rolAnterior = $usuario->rol->value;
        $usuario->update($data);

        // Desactivar, cambiar contraseña o cambiar rol cierra todas sus sesiones.
        if ((isset($data['activo']) && ! $data['activo']) || isset($data['password']) || (isset($data['rol']) && $data['rol'] !== $rolAnterior)) {
            $usuario->tokens()->delete();
        }

        $this->auditoria->registrar($request->user(), 'usuario.editado', 'usuario', $usuario->id, [
            'campos' => array_values(array_diff(array_keys($data), ['password'])),
            'password_cambiada' => isset($data['password']),
        ]);

        return new UserResource($usuario->fresh());
    }

    public function sesiones(User $usuario): JsonResponse
    {
        return response()->json(['data' => $usuario->tokens()->orderByDesc('last_used_at')->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'nombre' => $t->name,
                'ultimo_uso' => $t->last_used_at,
                'expira_en' => $t->expires_at,
                'creada' => $t->created_at,
            ])]);
    }

    /** Cierre de sesión remoto de todos los dispositivos de un usuario. */
    public function revocarSesiones(Request $request, User $usuario): JsonResponse
    {
        $n = $usuario->tokens()->delete();
        $this->auditoria->registrar($request->user(), 'usuario.sesiones_revocadas', 'usuario', $usuario->id, ['cantidad' => $n]);

        return response()->json(['message' => "Se cerraron {$n} sesión(es)."]);
    }

    /** Cierre de sesión remoto de un dispositivo concreto. */
    public function revocarSesion(Request $request, User $usuario, int $token): JsonResponse
    {
        $t = PersonalAccessToken::query()
            ->where('tokenable_type', $usuario->getMorphClass())
            ->where('tokenable_id', $usuario->id)
            ->findOrFail($token);
        $t->delete();
        $this->auditoria->registrar($request->user(), 'usuario.sesion_revocada', 'usuario', $usuario->id, ['token_id' => $token]);

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
