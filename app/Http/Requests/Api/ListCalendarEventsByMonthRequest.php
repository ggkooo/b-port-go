<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ListCalendarEventsByMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:1900'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.required' => 'O mês é obrigatório.',
            'month.integer' => 'O mês deve ser um número inteiro.',
            'month.between' => 'O mês deve estar entre 1 e 12.',
            'year.required' => 'O ano é obrigatório.',
            'year.integer' => 'O ano deve ser um número inteiro.',
            'year.min' => 'O ano deve ser maior ou igual a 1900.',
        ];
    }
}
