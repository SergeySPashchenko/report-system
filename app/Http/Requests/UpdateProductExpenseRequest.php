<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProductExpenseRequest extends FormRequest
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
        /** @var Expense|null $expense */
        $expense = $this->route('expense');

        return [
            'ExpenseID' => ['sometimes', 'integer', 'exists:expensetypes,ExpenseTypeID'],
            'ExpenseDate' => ['sometimes', 'date'],
            'Expense' => ['sometimes', 'numeric', 'min:0'],
            'external_id' => ['sometimes', 'nullable', 'integer', Rule::unique('expenses', 'external_id')->ignore($expense)],
            // ProductID заборонено змінювати через nested route
            'ProductID' => ['prohibited'],
        ];
    }
}
