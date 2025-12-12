<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductProductItemRequest extends FormRequest
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
            'ItemID' => ['nullable', 'integer', 'unique:product_items,ItemID'],
            'ProductName' => ['required', 'string', 'max:255'],
            'SKU' => ['required', 'string', 'max:255'],
            'Quantity' => ['required', 'integer', 'min:0'],
            'upSell' => ['sometimes', 'boolean'],
            'extraProduct' => ['sometimes', 'boolean'],
            'offerProducts' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'deleted' => ['sometimes', 'boolean'],
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
            'ItemID.unique' => 'An product item with this ItemID already exists.',
            'ProductName.required' => 'The ProductName field is required.',
            'SKU.required' => 'The SKU field is required.',
            'Quantity.required' => 'The Quantity field is required.',
            'Quantity.min' => 'The Quantity must be at least 0.',
            'ProductID.prohibited' => 'The ProductID field is prohibited. It is automatically set from the URL.',
        ];
    }
}
