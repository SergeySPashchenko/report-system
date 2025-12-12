<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreCategoryProductRequest extends FormRequest
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
            'gender_id' => ['sometimes', 'nullable', 'exists:genders,id'],
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
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        /** @var Category|null $category */
        $category = $this->route('category');

        // Встановлюємо як main_category_id за замовчуванням, якщо не вказано
        // Користувач може вказати marketing_category_id окремо
        if ($category !== null && ! $this->has('main_category_id')) {
            $this->merge([
                'main_category_id' => $category->id,
            ]);
        }
    }
}
