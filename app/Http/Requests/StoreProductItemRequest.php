<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreProductItemRequest extends FormRequest
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
            'ProductID' => ['required', 'integer', 'exists:products,ProductID'],
            'ProductName' => ['required', 'string', 'max:255'],
            'SKU' => ['required', 'string', 'max:255'],
            'Quantity' => ['required', 'integer', 'min:0'],
            'upSell' => ['sometimes', 'boolean'],
            'extraProduct' => ['sometimes', 'boolean'],
            'offerProducts' => ['nullable', 'string', 'max:255'],
            'active' => ['sometimes', 'boolean'],
            'deleted' => ['sometimes', 'boolean'],
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
            'ProductID.required' => 'The ProductID field is required.',
            'ProductID.exists' => 'The selected ProductID does not exist.',
            'ProductName.required' => 'The ProductName field is required.',
            'SKU.required' => 'The SKU field is required.',
            'Quantity.required' => 'The Quantity field is required.',
            'Quantity.min' => 'The Quantity must be at least 0.',
        ];
    }
}
