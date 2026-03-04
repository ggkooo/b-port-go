<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statement' => ['required', 'string'],
            'alternative_a' => ['required', 'string', 'max:255'],
            'alternative_b' => ['required', 'string', 'max:255'],
            'alternative_c' => ['required', 'string', 'max:255'],
            'alternative_d' => ['required', 'string', 'max:255'],
            'correct_alternative' => ['required', 'string', Rule::in(['a', 'b', 'c', 'd'])],
            'tip' => ['required', 'string'],
            'difficulty_id' => ['required', 'integer', Rule::exists('difficulties', 'id')],
            'class_id' => ['required', 'integer', Rule::exists('classes', 'id')],
            'activity_type_id' => ['required', 'integer', Rule::exists('activity_types', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statement.required' => 'O enunciado é obrigatório.',
            'alternative_a.required' => 'A alternativa A é obrigatória.',
            'alternative_b.required' => 'A alternativa B é obrigatória.',
            'alternative_c.required' => 'A alternativa C é obrigatória.',
            'alternative_d.required' => 'A alternativa D é obrigatória.',
            'correct_alternative.required' => 'A alternativa correta é obrigatória.',
            'correct_alternative.in' => 'A alternativa correta deve ser a, b, c ou d.',
            'tip.required' => 'A dica é obrigatória.',
            'difficulty_id.required' => 'A dificuldade é obrigatória.',
            'difficulty_id.integer' => 'A dificuldade selecionada é inválida.',
            'difficulty_id.exists' => 'A dificuldade selecionada é inválida.',
            'class_id.required' => 'A série é obrigatória.',
            'class_id.integer' => 'A série selecionada é inválida.',
            'class_id.exists' => 'A série selecionada é inválida.',
            'activity_type_id.required' => 'O tipo de atividade é obrigatório.',
            'activity_type_id.integer' => 'O tipo de atividade selecionado é inválido.',
            'activity_type_id.exists' => 'O tipo de atividade selecionado é inválido.',
        ];
    }
}
