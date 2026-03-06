<?php

namespace App\Http\Requests\Crm;

use App\Models\Core\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.update') ?? false;
    }

    public function rules(): array
    {
        $clienteId = $this->input('cliente_id');
        $tipoDoc = $this->input('tipo_documento');
        $numDoc = $this->input('numero_documento');

        $docUnique = Rule::unique('clientes', 'numero_documento')
            ->where('tipo_documento', $tipoDoc)
            ->when($clienteId, fn ($q) => $q->where('id', '!=', $clienteId));

        return [
            'tipo_documento' => ['required', Rule::in(['DNI', 'CE'])],
            'numero_documento' => ['required', 'string', 'max:20', $docUnique],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'activar_membresia' => ['nullable', 'boolean'],
            'membresia_id' => ['nullable', 'required_if:activar_membresia,true', 'exists:membresias,id'],
            'pago' => ['nullable', 'array'],
            'pago.monto' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
