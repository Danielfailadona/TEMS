<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'violation_type_id' => ['required', 'exists:violation_types,id', function ($attribute, $value, $fail) {
                if (! \App\Models\ViolationType::where('id', $value)->where('is_active', true)->exists()) {
                    $fail('The selected violation type is not available.');
                }
            }],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'vehicle_make' => ['nullable', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'vehicle_color' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_license' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'evidence' => ['nullable', 'array', 'max:10'],
            'evidence.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }
}
