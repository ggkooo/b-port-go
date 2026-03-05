<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDailyChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge_id' => ['sometimes', 'integer', Rule::exists('challenges', 'id')],
            'challenge_name' => ['sometimes', 'string', 'max:255'],
            'unit' => ['sometimes', 'string', 'max:255'],
            'target_value' => ['sometimes', 'integer', 'min:1'],
            'current_value' => ['sometimes', 'integer', 'min:0'],
            'xp_reward' => ['sometimes', 'integer', 'min:0'],
            'challenge_date' => ['sometimes', 'date'],
            'position' => ['sometimes', 'integer', 'min:1'],
            'completed_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'challenge_id.integer' => 'O ID do desafio deve ser um inteiro.',
            'challenge_id.exists' => 'O desafio informado não existe.',
            'challenge_name.string' => 'O nome do desafio deve ser uma string.',
            'challenge_name.max' => 'O nome do desafio não pode exceder 255 caracteres.',
            'unit.string' => 'A unidade deve ser uma string.',
            'unit.max' => 'A unidade não pode exceder 255 caracteres.',
            'target_value.integer' => 'O valor alvo deve ser um inteiro.',
            'target_value.min' => 'O valor alvo deve ser no mínimo 1.',
            'current_value.integer' => 'O valor atual deve ser um inteiro.',
            'current_value.min' => 'O valor atual deve ser no mínimo 0.',
            'xp_reward.integer' => 'A recompensa XP deve ser um inteiro.',
            'xp_reward.min' => 'A recompensa XP deve ser no mínimo 0.',
            'challenge_date.date' => 'A data do desafio deve ser uma data válida.',
            'position.integer' => 'A posição deve ser um inteiro.',
            'position.min' => 'A posição deve ser no mínimo 1.',
            'completed_at.date_format' => 'A data de conclusão deve estar no formato Y-m-d H:i:s.',
        ];
    }
}
