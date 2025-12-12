<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGenderProductRequest extends FormRequest
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
        /** @var Product|null $product */
        $product = $this->route('product');

        return [
            'ProductID' => ['sometimes', 'integer', Rule::unique('products', 'ProductID')->ignore($product)],
            'Product' => ['sometimes', 'string', 'max:255'],
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
            'gender_id.prohibited' => 'Gender ID is automatically set from the URL and cannot be changed.',
        ];
    }

    // gender_id встановлюється в контролері після валідації
    // щоб уникнути конфлікту з правилом 'prohibited'
}
