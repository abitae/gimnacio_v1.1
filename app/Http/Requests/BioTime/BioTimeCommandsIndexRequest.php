<?php

declare(strict_types=1);

namespace App\Http\Requests\BioTime;

use Illuminate\Foundation\Http\FormRequest;

class BioTimeCommandsIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ];
    }
}
