<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserXpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'xp' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'xp.required' => 'A quantidade de XP é obrigatória.',
            'xp.integer' => 'A quantidade de XP deve ser um número inteiro.',
            'xp.min' => 'A quantidade de XP deve ser maior que zero.',
        ];
    }
}
