<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreGenderProductRequest extends FormRequest
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
            'ProductID' => ['required', 'integer', 'unique:products,ProductID'],
            'Product' => ['required', 'string', 'max:255'],
            'newSystem' => ['sometimes', 'boolean'],
            'Visible' => ['sometimes', 'boolean'],
            'flyer' => ['sometimes', 'boolean'],
            'brand_id' => ['sometimes', 'nullable', 'exists:brands,id'],
            'main_category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'marketing_category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'gender_id' => ['prohibited'], // Не можна змінити через nested route
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
            'ProductID.required' => 'The ProductID field is required.',
            'ProductID.unique' => 'A product with this ProductID already exists.',
            'Product.required' => 'The Product field is required.',
            'gender_id.prohibited' => 'Gender ID is automatically set from the URL and cannot be changed.',
        ];
    }

    // gender_id встановлюється в контролері після валідації
    // щоб уникнути конфлікту з правилом 'prohibited'
}
