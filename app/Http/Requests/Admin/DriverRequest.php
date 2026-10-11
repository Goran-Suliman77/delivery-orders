<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


class DriverRequest extends FormRequest
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
        $driverId = $this->route('driverId');

        return [
            'vehicle_type' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],

            'vehicle_number' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('drivers', 'vehicle_number')
                    ->ignore($driverId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_type.required' =>
            'نوع المركبة مطلوب.',

            'vehicle_number.required' =>
            'رقم المركبة مطلوب.',

            'vehicle_number.unique' =>
            'رقم المركبة مستخدم لسائق آخر.',
        ];
    }
}
