<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClampingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_plate' => ['required', 'string', 'max:20'],
            'location' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string'],
            'evidence' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'evidence.required' => 'An evidence photo is required.',
            'evidence.image' => 'The evidence file must be an image.',
            'evidence.mimes' => 'The evidence photo must be a JPEG, PNG, or WebP.',
            'evidence.max' => 'The evidence photo must be 5 MB or smaller.',
        ];
    }
}
