<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

class OrderReportRequest extends FormRequest
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
            'from' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'to' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'store_id' => [
                'nullable',
                'integer',
                'min:1',
                'exists:stores,id',
            ],
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (
                $validator->errors()->has('from')
                || $validator->errors()->has('to')
            ) {
                return;
            }

            $from = $this->input('from') ?: null;
            $to = $this->input('to') ?: null;

            $effectiveTo = $to
                ?? today()->toDateString();

            $effectiveFrom = $from
                ?? (
                    $to
                    ? CarbonImmutable::parse($to)
                    ->subDays(29)
                    ->toDateString()
                    : today()->subDays(29)->toDateString()
                );

            if ($effectiveTo < $effectiveFrom) {
                $validator->errors()->add(
                    'to',
                    'يجب ألا يكون تاريخ النهاية قبل تاريخ البداية.'
                );

                return;
            }

            $days = CarbonImmutable::parse($effectiveFrom)
                ->diffInDays(
                    CarbonImmutable::parse($effectiveTo)
                ) + 1;

            if ($days > 366) {
                $validator->errors()->add(
                    'from',
                    'لا يمكن أن تتجاوز مدة التقرير 366 يومًا.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'from.date_format' =>
            'تاريخ البداية يجب أن يكون بالصيغة YYYY-MM-DD.',

            'to.date_format' =>
            'تاريخ النهاية يجب أن يكون بالصيغة YYYY-MM-DD.',

            'from.before_or_equal' =>
            'تاريخ البداية لا يمكن أن يكون في المستقبل.',

            'to.before_or_equal' =>
            'تاريخ النهاية لا يمكن أن يكون في المستقبل.',

            'store_id.exists' =>
            'المتجر المحدد غير موجود.',
        ];
    }
}
