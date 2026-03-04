<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDailyChallengeProgressRequest extends FormRequest
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
            'increment' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'increment.required' => 'O valor de progresso é obrigatório.',
            'increment.integer' => 'O valor de progresso deve ser um número inteiro.',
            'increment.min' => 'O valor de progresso deve ser maior que zero.',
        ];
    }
}
