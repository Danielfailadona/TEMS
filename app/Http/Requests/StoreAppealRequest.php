<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'citation_id' => ['required', 'exists:citations,id'],
            'reason' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
