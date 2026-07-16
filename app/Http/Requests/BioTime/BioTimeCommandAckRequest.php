<?php

declare(strict_types=1);

namespace App\Http\Requests\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BioTimeCommandAckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    BioTimeAccessCommand::STATUS_ACKED,
                    BioTimeAccessCommand::STATUS_FAILED,
                ]),
            ],
            'error' => ['nullable', 'string', 'max:2000'],
            'biotime_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
