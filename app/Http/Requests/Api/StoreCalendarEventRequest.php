<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_date' => ['required', 'date'],
            'event_time' => ['required', 'date_format:H:i'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_date.required' => 'A data do evento é obrigatória.',
            'event_date.date' => 'A data do evento deve ser uma data válida.',
            'event_time.required' => 'A hora do evento é obrigatória.',
            'event_time.date_format' => 'A hora do evento deve estar no formato HH:MM.',
            'name.required' => 'O nome do evento é obrigatório.',
            'name.string' => 'O nome do evento deve ser uma string.',
            'name.max' => 'O nome do evento não pode exceder 255 caracteres.',
            'description.required' => 'A descrição do evento é obrigatória.',
            'description.string' => 'A descrição do evento deve ser uma string.',
        ];
    }
}
