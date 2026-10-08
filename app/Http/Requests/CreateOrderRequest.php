<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],

            'store_id' => [
                'required',
                'integer',
                Rule::exists('stores', 'id')
                    ->where(
                        fn ($query) =>
                            $query->where('is_active', true)
                    ),
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*' => [
                'required',
                'array',
            ],

            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],

            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' =>
                'الزبون مطلوب.',

            'customer_id.exists' =>
                'الزبون غير موجود.',

            'store_id.required' =>
                'المتجر مطلوب.',

            'store_id.exists' =>
                'المتجر غير موجود أو غير متاح حاليًا.',

            'items.required' =>
                'يجب إضافة منتجات إلى الطلب.',

            'items.min' =>
                'يجب أن يحتوي الطلب على منتج واحد على الأقل.',

            'items.*.product_id.required' =>
                'معرف المنتج مطلوب.',

            'items.*.product_id.exists' =>
                'أحد المنتجات المطلوبة غير موجود.',

            'items.*.product_id.distinct' =>
                'لا يمكن تكرار المنتج داخل الطلب.',

            'items.*.quantity.required' =>
                'كمية المنتج مطلوبة.',

            'items.*.quantity.integer' =>
                'الكمية يجب أن تكون رقمًا صحيحًا.',

            'items.*.quantity.min' =>
                'الكمية يجب أن تكون أكبر من صفر.',

            'items.*.quantity.max' =>
                'الحد الأقصى للكمية هو 100.',
        ];
    }
}
