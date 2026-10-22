<?php

namespace App\Http\Requests;

use App\Models\ViolationType;
use Illuminate\Foundation\Http\FormRequest;

class StoreCitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $selectedType = ViolationType::find(request()->input('violation_type_id'));
        $isCustom = $selectedType?->isCustom() ?? false;

        return [
            'violation_type_id' => ['required', 'exists:violation_types,id', function ($attribute, $value, $fail) {
                if (! ViolationType::where('id', $value)->where('is_active', true)->exists()) {
                    $fail('The selected violation type is not available.');
                }
            }],
            'custom_violation_name' => [$isCustom ? 'required' : 'nullable', 'string', 'max:255'],
            'penalty_amount' => [$isCustom ? 'required' : 'nullable', 'numeric', 'min:0'],
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'vehicle_make' => ['nullable', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'vehicle_type' => ['nullable', 'string', 'max:100'],
            'vehicle_color' => ['nullable', 'string', 'max:50'],
            'driver_name' => ['nullable', 'string', 'max:255'],
            'driver_license' => ['nullable', 'string', 'max:50'],
            'location' => ['required', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string'],
            'evidence' => ['required', 'array', 'min:1', 'max:10'],
            'evidence.*' => ['file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'impounding_notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'At least one evidence photo is required.',
            'evidence.min' => 'At least one evidence photo is required.',
            'evidence.max' => 'You may upload up to 10 evidence photos.',
            'evidence.*.image' => 'Each evidence file must be an image.',
            'evidence.*.mimes' => 'Evidence photos must be JPEG, PNG, or WebP.',
            'evidence.*.max' => 'Each evidence photo must be 5 MB or smaller.',
        ];
    }
}
