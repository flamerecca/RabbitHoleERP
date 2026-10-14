<?php

namespace App\Http\Requests;

use App\Enums\DocumentDateFormat;
use App\Enums\DocumentResetPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDocumentSequenceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prefix' => ['present', 'nullable', 'string', 'max:20'],
            'date_format' => ['required', Rule::enum(DocumentDateFormat::class)],
            'separator' => ['present', 'nullable', 'string', 'max:5'],
            'padding' => ['required', 'integer', 'between:3,10'],
            'reset_period' => ['required', Rule::enum(DocumentResetPeriod::class)],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $resetPeriod = DocumentResetPeriod::tryFrom((string) $this->input('reset_period'));
                $dateFormat = DocumentDateFormat::tryFrom((string) $this->input('date_format'));

                if ($resetPeriod !== null && $dateFormat !== null && ! $resetPeriod->isCompatibleWith($dateFormat)) {
                    $validator->errors()->add('reset_period', __('The reset period needs a date segment that shows at least the same period.'));
                }
            },
        ];
    }
}
