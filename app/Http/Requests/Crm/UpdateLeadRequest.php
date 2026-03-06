<?php

namespace App\Http\Requests\Crm;

use App\Models\Crm\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crm.update') ?? false;
    }

    public function rules(): array
    {
        $leadId = $this->route('lead')?->id ?? $this->route('id');
        $telefonoUnique = Rule::unique('crm_leads', 'telefono')
            ->ignore($leadId)
            ->whereNull('deleted_at');

        return [
            'telefono' => ['sometimes', 'required', 'string', 'max:20', $telefonoUnique],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'nombres' => ['nullable', 'string', 'max:100'],
            'apellidos' => ['nullable', 'string', 'max:100'],
            'tipo_documento' => ['nullable', Rule::in(['DNI', 'CE'])],
            'numero_documento' => [
                'nullable',
                'string',
                'max:20',
                function ($attr, $value, $fail) use ($leadId) {
                    if (!$value || !$this->input('tipo_documento')) {
                        return;
                    }
                    $q = Lead::where('tipo_documento', $this->input('tipo_documento'))
                        ->where('numero_documento', $value);
                    if ($leadId) {
                        $q->where('id', '!=', $leadId);
                    }
                    if ($q->exists()) {
                        $fail('Ya existe un lead con este documento.');
                    }
                },
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'canal_origen' => ['nullable', 'string', 'max:60'],
            'sede' => ['nullable', 'string', 'max:80'],
            'interes_principal' => ['nullable', 'string', 'max:120'],
            'stage_id' => ['sometimes', 'exists:crm_stages,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notas' => ['nullable', 'string'],
        ];
    }
}
