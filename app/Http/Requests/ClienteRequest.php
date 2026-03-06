<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $isUpdate = $this->route('cliente') !== null;
        $clienteId = $isUpdate ? $this->route('cliente') : null;

        return [
            'tipo_documento' => [$isUpdate ? 'sometimes' : 'required', 'in:DNI,CE'],
            'numero_documento' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'numero_documento')
                    ->where('tipo_documento', $this->input('tipo_documento'))
                    ->ignore($clienteId),
            ],
            'nombres' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'apellidos' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['nullable', 'string'],
            'estado_cliente' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:activo,inactivo,suspendido'],
            'biotime_state' => ['nullable', 'boolean'],
            'biotime_update' => ['nullable', 'boolean'],
            'foto' => ['nullable', 'string'],
            'datos_salud' => ['nullable', 'array'],
            'datos_emergencia' => ['nullable', 'array'],
            'consentimientos' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento debe ser DNI o CE.',
            'numero_documento.required' => 'El número de documento es obligatorio.',
            'numero_documento.unique' => 'Ya existe un cliente con este tipo y número de documento.',
            'numero_documento.max' => 'El número de documento no puede exceder 20 caracteres.',
            'nombres.required' => 'Los nombres son obligatorios.',
            'nombres.max' => 'Los nombres no pueden exceder 100 caracteres.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.max' => 'Los apellidos no pueden exceder 100 caracteres.',
            'telefono.max' => 'El teléfono no puede exceder 20 caracteres.',
            'email.email' => 'El email debe ser una dirección de correo válida.',
            'email.max' => 'El email no puede exceder 255 caracteres.',
            'estado_cliente.required' => 'El estado del cliente es obligatorio.',
            'estado_cliente.in' => 'El estado debe ser: activo, inactivo o suspendido.',
        ];
    }
}

