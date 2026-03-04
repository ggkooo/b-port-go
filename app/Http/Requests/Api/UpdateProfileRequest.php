<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'school' => ['required', 'string', 'max:255'],
            'class' => ['required', 'integer', Rule::exists('classes', 'id')],
            'shift' => ['required', 'integer', Rule::exists('shifts', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'O telefone é obrigatório.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'state.required' => 'O estado é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'school.required' => 'A escola é obrigatória.',
            'class.required' => 'A turma é obrigatória.',
            'class.integer' => 'A turma selecionada é inválida.',
            'class.exists' => 'A turma selecionada é inválida.',
            'shift.required' => 'O turno é obrigatório.',
            'shift.integer' => 'O turno selecionado é inválido.',
            'shift.exists' => 'O turno selecionado é inválido.',
        ];
    }
}
