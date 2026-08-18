<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class LoginClienteAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', 'in:DNI,CE'],
            'numero_documento' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento debe ser DNI o CE.',
            'numero_documento.required' => 'El número de documento es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
