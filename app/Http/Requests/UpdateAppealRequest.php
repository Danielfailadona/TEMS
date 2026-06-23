<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['under_review', 'approved', 'rejected'])],
            'decision_notes' => ['nullable', 'string'],
            'adjusted_amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }
}
