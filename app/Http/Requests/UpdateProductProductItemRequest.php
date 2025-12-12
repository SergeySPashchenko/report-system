<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProductProductItemRequest extends FormRequest
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
            'ItemID' => ['sometimes', 'integer', 'unique:product_items,ItemID,'.$this->route('product_item')?->id.',id'],
            'ProductName' => ['sometimes', 'string', 'max:255'],
            'SKU' => ['sometimes', 'string', 'max:255'],
            'Quantity' => ['sometimes', 'integer', 'min:0'],
            'upSell' => ['sometimes', 'boolean'],
            'extraProduct' => ['sometimes', 'boolean'],
            'offerProducts' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'deleted' => ['sometimes', 'boolean'],
            // ProductID заборонено змінювати через nested route
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
            'ItemID.unique' => 'An product item with this ItemID already exists.',
            'Quantity.min' => 'The Quantity must be at least 0.',
            'ProductID.prohibited' => 'The ProductID field is prohibited. It cannot be changed through this route.',
        ];
    }
}
