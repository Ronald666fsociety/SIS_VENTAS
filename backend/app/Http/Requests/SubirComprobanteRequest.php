<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubirComprobanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comprobante' => ['required', 'file', 'max:8192'], // 8 MB; el MIME real se valida al re-codificar
            'nro_transaccion' => ['nullable', 'string', 'max:60'],
        ];
    }
}
