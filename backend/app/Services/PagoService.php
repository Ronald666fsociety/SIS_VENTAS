<?php

namespace App\Services;

use App\Enums\EstadoPago;
use App\Enums\EstadoPedido;
use App\Enums\Rol;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class PagoService
{
    public function __construct(
        private PedidoService $pedidos,
        private ImagenService $imagenes,
        private NotificadorService $notificador,
        private AuditoriaService $auditoria,
    ) {
    }

    public function subirComprobante(Pedido $pedido, UploadedFile $archivo, ?string $nroTransaccion, User $cliente): Pago
    {
        $max = config('tienda.max_intentos_pago');

        try {
            $pago = DB::transaction(function () use ($pedido, $archivo, $nroTransaccion, $cliente, $max) {
                $p = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
                /** @var Pago $pago */
                $pago = Pago::query()->where('pedido_id', $p->id)->lockForUpdate()->firstOrFail();

                if ($p->estado !== EstadoPedido::PendientePago) {
                    throw ValidationException::withMessages(['estado' => 'Este pedido ya no admite comprobantes.']);
                }
                if ($pago->intentos >= $max) {
                    throw ValidationException::withMessages(['comprobante' => 'Superaste el máximo de intentos. Contáctanos por WhatsApp.']);
                }
                if ($nroTransaccion !== null && $nroTransaccion !== '') {
                    $duplicado = Pago::query()
                        ->where('nro_transaccion', $nroTransaccion)
                        ->where('estado', '!=', EstadoPago::Rechazado->value)
                        ->where('id', '!=', $pago->id)
                        ->exists();
                    if ($duplicado) {
                        throw ValidationException::withMessages(['nro_transaccion' => 'Este número de transacción ya fue usado.']);
                    }
                }

                $ruta = $this->imagenes->guardar($archivo, config('tienda.disco_privado'), 'comprobantes/'.$p->id);
                $anterior = $pago->comprobante_path;

                $pago->update([
                    'estado' => EstadoPago::EnRevision,
                    'comprobante_path' => $ruta,
                    'nro_transaccion' => $nroTransaccion ?: null,
                    'intentos' => $pago->intentos + 1,
                    'motivo_rechazo' => null,
                ]);
                if ($anterior) {
                    Storage::disk(config('tienda.disco_privado'))->delete($anterior);
                }

                $this->pedidos->cambiarEstado($p, EstadoPedido::PagoEnRevision, $cliente, 'Comprobante subido', validar: false);

                return $pago;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['nro_transaccion' => 'Este número de transacción ya fue usado.']);
        }

        $this->notificador->aPersonal(
            [Rol::Operador, Rol::Admin],
            'Comprobante por verificar',
            "Pedido {$pedido->codigo} · Bs {$pago->monto}",
            $pedido,
        );

        return $pago;
    }

    public function aprobar(Pago $pago, User $staff): Pago
    {
        return DB::transaction(function () use ($pago, $staff) {
            $pago = Pago::query()->lockForUpdate()->findOrFail($pago->id);
            if (! in_array($pago->estado, [EstadoPago::EnRevision, EstadoPago::Pendiente], true)) {
                throw ValidationException::withMessages(['estado' => 'Este pago no está en revisión.']);
            }

            $pago->update([
                'estado' => EstadoPago::Verificado,
                'verificado_por' => $staff->id,
                'verificado_en' => now(),
                'motivo_rechazo' => null,
            ]);

            $pedido = Pedido::findOrFail($pago->pedido_id);
            $this->pedidos->cambiarEstado($pedido, EstadoPedido::PagoVerificado, $staff, 'Pago aprobado', validar: false, notificar: false);
            $this->pedidos->cambiarEstado($pedido, EstadoPedido::Preparando, $staff, 'En preparación');

            $this->auditoria->registrar($staff, 'pago.aprobado', 'pago', $pago->id, [
                'pedido_id' => $pedido->id,
                'monto' => (string) $pago->monto,
            ]);

            return $pago->fresh();
        });
    }

    public function rechazar(Pago $pago, User $staff, string $motivo): Pago
    {
        return DB::transaction(function () use ($pago, $staff, $motivo) {
            $pago = Pago::query()->lockForUpdate()->findOrFail($pago->id);
            if (! in_array($pago->estado, [EstadoPago::EnRevision, EstadoPago::Pendiente], true)) {
                throw ValidationException::withMessages(['estado' => 'Este pago no está en revisión.']);
            }

            $pago->update([
                'estado' => EstadoPago::Rechazado,
                'motivo_rechazo' => $motivo,
                'verificado_por' => $staff->id,
                'verificado_en' => now(),
            ]);

            $pedido = Pedido::findOrFail($pago->pedido_id);
            $max = config('tienda.max_intentos_pago');

            if ($pago->intentos >= $max) {
                $this->pedidos->cambiarEstado($pedido, EstadoPedido::Cancelado, $staff, "Pago rechazado definitivamente: {$motivo}", validar: false);
            } else {
                $restantes = $max - $pago->intentos;
                $this->pedidos->cambiarEstado(
                    $pedido,
                    EstadoPedido::PendientePago,
                    $staff,
                    "{$motivo}. Te quedan {$restantes} intento(s).",
                    validar: false,
                );
            }

            $this->auditoria->registrar($staff, 'pago.rechazado', 'pago', $pago->id, [
                'pedido_id' => $pedido->id,
                'motivo' => $motivo,
            ]);

            return $pago->fresh();
        });
    }

    public function urlComprobante(Pago $pago): ?string
    {
        if (! $pago->comprobante_path) {
            return null;
        }

        $relative = URL::temporarySignedRoute(
            'comprobantes.ver',
            now()->addMinutes(config('tienda.url_comprobante_minutos')),
            ['pago' => $pago->id],
            absolute: false,
        );

        $host = request()?->getSchemeAndHttpHost() ?: rtrim(config('app.url'), '/');

        return $host.$relative;
    }
}
