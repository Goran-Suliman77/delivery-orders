<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'driver_id' => [
                'required',
                'integer',
                'exists:drivers,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required' =>
            'يجب تحديد السائق المطلوب.',

            'driver_id.integer' =>
            'معرّف السائق يجب أن يكون رقمًا صحيحًا.',

            'driver_id.exists' =>
            'السائق المحدد غير موجود.',
        ];
    }
}
