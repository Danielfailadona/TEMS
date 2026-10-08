<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(['citation', 'clamping', 'impounding'])],
            'citation_id' => ['required_if:category,citation', 'nullable', 'exists:citations,id'],
            'clamping_id' => ['required_if:category,clamping', 'nullable', 'exists:clamping_records,id'],
            'impounding_id' => ['required_if:category,impounding', 'nullable', 'exists:impounding_records,id'],
            'amount' => ['required_if:category,clamping,impounding', 'nullable', 'numeric', 'min:0', 'max:9999999'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'category.required' => 'Choose what you are collecting payment for.',
            'citation_id.required_if' => 'Select a citation to pay.',
            'clamping_id.required_if' => 'Select a clamping notice to pay.',
            'impounding_id.required_if' => 'Select an impounding notice to pay.',
            'amount.required_if' => 'Enter the amount received.',
        ];
    }

    public function attributes(): array
    {
        return [
            'citation_id' => 'citation',
            'clamping_id' => 'clamping notice',
            'impounding_id' => 'impounding notice',
            'amount' => 'amount',
            'reference_number' => 'reference number',
        ];
    }
}