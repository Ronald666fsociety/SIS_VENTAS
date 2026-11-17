<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case PendientePago = 'pendiente_pago';
    case PagoEnRevision = 'pago_en_revision';
    case PagoVerificado = 'pago_verificado';
    case Preparando = 'preparando';
    case ListoParaEnvio = 'listo_para_envio';
    case EnCamino = 'en_camino';
    case Entregado = 'entregado';
    case Cancelado = 'cancelado';
    case EntregaFallida = 'entrega_fallida';

    /** Transiciones permitidas para el cambio manual de estado (operador/admin). */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PendientePago => [self::Cancelado],
            self::PagoEnRevision => [self::Cancelado],
            self::PagoVerificado => [self::Preparando, self::Cancelado],
            self::Preparando => [self::ListoParaEnvio, self::Cancelado],
            self::ListoParaEnvio => [self::EnCamino, self::Cancelado],
            self::EnCamino => [self::Entregado, self::EntregaFallida],
            self::EntregaFallida => [self::ListoParaEnvio, self::Cancelado],
            self::Entregado, self::Cancelado => [],
        };
    }

    public function puedePasarA(self $nuevo): bool
    {
        return in_array($nuevo, $this->transicionesPermitidas(), true);
    }

    /** El cliente solo puede cancelar mientras el pago no esté verificado. */
    public function cancelablePorCliente(): bool
    {
        return in_array($this, [self::PendientePago, self::PagoEnRevision], true);
    }

    public function esFinal(): bool
    {
        return in_array($this, [self::Entregado, self::Cancelado], true);
    }

    /** Estados que cuentan como venta efectiva en reportes. */
    public static function vendidos(): array
    {
        return [
            self::PagoVerificado, self::Preparando, self::ListoParaEnvio,
            self::EnCamino, self::Entregado, self::EntregaFallida,
        ];
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendientePago => 'Pendiente de pago',
            self::PagoEnRevision => 'Verificando tu pago',
            self::PagoVerificado => 'Pago verificado',
            self::Preparando => 'Preparando tu pedido',
            self::ListoParaEnvio => 'Listo para envío',
            self::EnCamino => 'En camino',
            self::Entregado => 'Entregado',
            self::Cancelado => 'Cancelado',
            self::EntregaFallida => 'Entrega fallida',
        };
    }
}
