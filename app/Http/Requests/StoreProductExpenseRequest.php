<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductExpenseRequest extends FormRequest
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
        return [
            'ExpenseID' => ['required', 'integer', 'exists:expensetypes,ExpenseTypeID'],
            'ExpenseDate' => ['required', 'date'],
            'Expense' => ['required', 'numeric', 'min:0'],
            'external_id' => ['nullable', 'integer', 'unique:expenses,external_id'],
            // ProductID заборонено вказувати - воно береться з URL
            'ProductID' => ['prohibited'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ExpenseID.required' => 'The ExpenseID field is required.',
            'ExpenseID.exists' => 'The selected ExpenseID does not exist.',
            'ExpenseDate.required' => 'The ExpenseDate field is required.',
            'ExpenseDate.date' => 'The ExpenseDate must be a valid date.',
            'Expense.required' => 'The Expense field is required.',
            'Expense.numeric' => 'The Expense must be a number.',
            'Expense.min' => 'The Expense must be at least 0.',
            'external_id.unique' => 'An expense with this external_id already exists.',
            'ProductID.prohibited' => 'The ProductID field is prohibited. It is automatically set from the URL.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // ProductID буде встановлено в контролері з URL
    }
}
