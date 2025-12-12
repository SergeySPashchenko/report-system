<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Expensetype;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateExpensetypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Expensetype|null $expensetype */
        $expensetype = $this->route('expensetype');

        return [
            'Name' => ['sometimes', 'string', 'max:255'],
            'ExpenseTypeID' => ['sometimes', 'nullable', 'integer', Rule::unique('expensetypes', 'ExpenseTypeID')->ignore($expensetype)],
        ];
    }
}
