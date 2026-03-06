<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CajaRequest extends FormRequest
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
        $action = $this->route()->getActionMethod();

        if ($action === 'abrir') {
            return [
                'saldo_inicial' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
                'observaciones_apertura' => ['nullable', 'string', 'max:1000'],
            ];
        }

        if ($action === 'cerrar') {
            return [
                'observaciones_cierre' => ['nullable', 'string', 'max:1000'],
            ];
        }

        return [];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'saldo_inicial.required' => 'El saldo inicial es obligatorio.',
            'saldo_inicial.numeric' => 'El saldo inicial debe ser un número válido.',
            'saldo_inicial.min' => 'El saldo inicial no puede ser negativo.',
            'saldo_inicial.regex' => 'El saldo inicial debe tener máximo 2 decimales.',
            'observaciones_apertura.max' => 'Las observaciones de apertura no pueden exceder 1000 caracteres.',
            'observaciones_cierre.max' => 'Las observaciones de cierre no pueden exceder 1000 caracteres.',
        ];
    }
}
