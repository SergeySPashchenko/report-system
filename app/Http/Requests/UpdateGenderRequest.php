<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateGenderRequest extends FormRequest
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
        /** @var Gender|null $gender */
        $gender = $this->route('gender');

        return [
            'gender_name' => ['sometimes', 'string', 'max:255'],
            'gender_id' => ['sometimes', 'nullable', 'integer', Rule::unique('genders', 'gender_id')->ignore($gender)],
        ];
    }
}
