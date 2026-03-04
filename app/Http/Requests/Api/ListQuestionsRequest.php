<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => ['nullable', 'integer', Rule::exists('classes', 'id')],
            'difficulty_id' => ['nullable', 'integer', Rule::exists('difficulties', 'id')],
            'activity_type_id' => ['nullable', 'integer', Rule::exists('activity_types', 'id')],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'class_id.integer' => 'A série selecionada é inválida.',
            'class_id.exists' => 'A série selecionada é inválida.',
            'difficulty_id.integer' => 'A dificuldade selecionada é inválida.',
            'difficulty_id.exists' => 'A dificuldade selecionada é inválida.',
            'activity_type_id.integer' => 'O tipo de atividade selecionado é inválido.',
            'activity_type_id.exists' => 'O tipo de atividade selecionado é inválido.',
            'quantity.integer' => 'A quantidade de questões deve ser um número inteiro.',
            'quantity.min' => 'A quantidade mínima de questões é 1.',
            'quantity.max' => 'A quantidade máxima de questões é 100.',
        ];
    }
}
