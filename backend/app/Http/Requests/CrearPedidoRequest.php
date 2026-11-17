<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearPedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.producto_id' => ['required', 'integer', 'distinct', 'exists:productos,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:999'],

            'direccion_id' => ['nullable', 'integer', 'required_without:direccion_texto'],
            'direccion_texto' => ['nullable', 'string', 'max:500', 'required_without:direccion_id'],
            'referencia' => ['nullable', 'string', 'max:300'],
            'lat' => ['nullable', 'numeric', 'between:-90,90', 'required_without:direccion_id'],
            'lng' => ['nullable', 'numeric', 'between:-180,180', 'required_without:direccion_id'],

            'notas_cliente' => ['nullable', 'string', 'max:500'],
            'metodo_pago' => ['nullable', 'in:qr,efectivo'],
        ];
    }
}
