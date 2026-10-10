<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\OrderStatus;


class UpdateOrderStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::enum(
                    \App\Enums\OrderStatus::class
                ),
                'not_in:assigned',
            ],
        ];
    }
    public function messages(): array
    {
        return [
            'status.required' =>
            'حالة الطلب الجديدة مطلوبة.',

            'status.string' =>
            'يجب أن تكون حالة الطلب نصًا.',

            'status.enum' =>
            'حالة الطلب غير معروفة في النظام.',
            
            'status.not_in' =>
            'يجب استخدام عملية تعيين السائق لتغيير الحالة إلى assigned.',
        ];
    }
}
