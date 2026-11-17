<?php

namespace App\Console\Commands;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/** Crea el primer admin desde el servidor (nadie se registra como admin desde la app). */
class MakeAdmin extends Command
{
    protected $signature = 'make:admin
        {--nombre= : Nombre}
        {--telefono= : Teléfono (usuario de acceso)}
        {--email= : Correo (opcional)}
        {--password= : Contraseña (si se omite se pide de forma oculta)}';

    protected $description = 'Crea un usuario administrador para la App Negocio';

    public function handle(): int
    {
        $nombre = $this->option('nombre') ?: text('Nombre', required: true);
        $telefono = $this->option('telefono') ?: text('Teléfono', required: true);
        $password = $this->option('password') ?: password('Contraseña (mínimo 8 caracteres)', required: true);

        $validator = Validator::make(
            ['nombre' => $nombre, 'telefono' => $telefono, 'email' => $this->option('email'), 'password' => $password],
            [
                'nombre' => ['required', 'string', 'max:100'],
                'telefono' => ['required', 'regex:/^\+?[0-9]{7,15}$/', 'unique:users,telefono'],
                'email' => ['nullable', 'email', 'unique:users,email'],
                'password' => ['required', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::create([
            'nombre' => $nombre,
            'telefono' => $telefono,
            'email' => $this->option('email') ?: null,
            'password' => $password,
            'rol' => Rol::Admin,
        ]);

        $this->info("Administrador creado (id {$user->id}). Inicia sesión en la App Negocio con el teléfono {$telefono}.");

        return self::SUCCESS;
    }
}
