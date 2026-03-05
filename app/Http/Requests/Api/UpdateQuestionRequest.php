<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statement' => ['sometimes', 'string'],
            'alternative_a' => ['sometimes', 'string', 'max:255'],
            'alternative_b' => ['sometimes', 'string', 'max:255'],
            'alternative_c' => ['sometimes', 'string', 'max:255'],
            'alternative_d' => ['sometimes', 'string', 'max:255'],
            'correct_alternative' => ['sometimes', 'string', Rule::in(['a', 'b', 'c', 'd'])],
            'tip' => ['sometimes', 'string'],
            'difficulty_id' => ['sometimes', 'integer', Rule::exists('difficulties', 'id')],
            'class_id' => ['sometimes', 'integer', Rule::exists('classes', 'id')],
            'activity_type_id' => ['sometimes', 'integer', Rule::exists('activity_types', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'statement.string' => 'O enunciado deve ser uma string.',
            'alternative_a.string' => 'A alternativa A deve ser uma string.',
            'alternative_a.max' => 'A alternativa A não pode exceder 255 caracteres.',
            'alternative_b.string' => 'A alternativa B deve ser uma string.',
            'alternative_b.max' => 'A alternativa B não pode exceder 255 caracteres.',
            'alternative_c.string' => 'A alternativa C deve ser uma string.',
            'alternative_c.max' => 'A alternativa C não pode exceder 255 caracteres.',
            'alternative_d.string' => 'A alternativa D deve ser uma string.',
            'alternative_d.max' => 'A alternativa D não pode exceder 255 caracteres.',
            'correct_alternative.string' => 'A alternativa correta deve ser uma string.',
            'correct_alternative.in' => 'A alternativa correta deve ser a, b, c ou d.',
            'tip.string' => 'A dica deve ser uma string.',
            'difficulty_id.integer' => 'A dificuldade selecionada é inválida.',
            'difficulty_id.exists' => 'A dificuldade selecionada é inválida.',
            'class_id.integer' => 'A série selecionada é inválida.',
            'class_id.exists' => 'A série selecionada é inválida.',
            'activity_type_id.integer' => 'O tipo de atividade selecionado é inválido.',
            'activity_type_id.exists' => 'O tipo de atividade selecionado é inválido.',
        ];
    }
}
