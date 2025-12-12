<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCategoryProductRequest extends FormRequest
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
            'gender_id' => ['sometimes', 'nullable', 'exists:genders,id'],
        ];
    }

    // Для оновлення не встановлюємо category_id автоматично
    // Користувач може явно вказати яку категорію змінити
}
