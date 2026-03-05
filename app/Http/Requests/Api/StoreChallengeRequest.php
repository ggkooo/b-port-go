<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'target_value' => ['required', 'integer', 'min:1'],
            'xp_reward' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do desafio é obrigatório.',
            'name.string' => 'O nome do desafio deve ser uma string.',
            'name.max' => 'O nome do desafio não pode exceder 255 caracteres.',
            'unit.required' => 'A unidade é obrigatória.',
            'unit.string' => 'A unidade deve ser uma string.',
            'unit.max' => 'A unidade não pode exceder 255 caracteres.',
            'target_value.required' => 'O valor alvo é obrigatório.',
            'target_value.integer' => 'O valor alvo deve ser um inteiro.',
            'target_value.min' => 'O valor alvo deve ser no mínimo 1.',
            'xp_reward.required' => 'A recompensa XP é obrigatória.',
            'xp_reward.integer' => 'A recompensa XP deve ser um inteiro.',
            'xp_reward.min' => 'A recompensa XP deve ser no mínimo 0.',
            'is_active.required' => 'O status ativo é obrigatório.',
            'is_active.boolean' => 'O status ativo deve ser um booleano.',
        ];
    }
}
