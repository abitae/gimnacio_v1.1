<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BioTimeSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entity' => ['required', 'string', Rule::in(['transactions', 'devices', 'areas', 'departments', 'employees'])],
            'timestamp' => ['required', 'string', 'max:32'],
            'data' => ['required', 'array', 'max:5000'],
            'data.*' => ['array'],
        ];
    }
}
