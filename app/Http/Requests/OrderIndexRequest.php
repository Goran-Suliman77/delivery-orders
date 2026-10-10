<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    //transform the input data before validation 
    protected function prepareForValidation(): void
    {
        $this->merge([
            'without_driver' => $this->normalizeBoolean(
                $this->input('without_driver')
            ),

            'today' => $this->normalizeBoolean(
                $this->input('today')
            ),
        ]);
    }

    private function normalizeBoolean(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower((string) $value)) {
            'true', '1' => true,
            'false', '0' => false,
            default => $value,
        };
    }

    public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                'string',
                Rule::enum(OrderStatus::class),
            ],

            'store_id' => [
                'nullable',
                'integer',
                'exists:stores,id',
            ],

            'driver_id' => [
                'nullable',
                'integer',
                'exists:drivers,id',
            ],

            'customer_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'without_driver' => [
                'nullable',
                'boolean',
            ],

            'today' => [
                'nullable',
                'boolean',
            ],

            'older_than_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.enum' =>
            'حالة الطلب غير صحيحة.',

            'store_id.exists' =>
            'المتجر غير موجود.',

            'driver_id.exists' =>
            'السائق غير موجود.',

            'customer_id.exists' =>
            'الزبون غير موجود.',

            'without_driver.boolean' =>
            'قيمة without_driver يجب أن تكون true أو false.',

            'today.boolean' =>
            'قيمة today يجب أن تكون true أو false.',

            'older_than_minutes.integer' =>
            'عدد الدقائق يجب أن يكون رقمًا صحيحًا.',

            'older_than_minutes.min' =>
            'عدد الدقائق يجب أن يكون أكبر من صفر.',

            'older_than_minutes.max' =>
            'الحد الأقصى هو 1440 دقيقة.',

            'per_page.integer' =>
            'per_page يجب أن يكون رقمًا صحيحًا.',

            'per_page.max' =>
            'الحد الأقصى لعدد العناصر في الصفحة هو 100.',
        ];
    }
}
