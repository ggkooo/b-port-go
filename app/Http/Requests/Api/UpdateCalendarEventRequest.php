<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'event_date' => ['sometimes', 'date'],
            'event_time' => ['sometimes', 'date_format:H:i'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'event_date.date' => 'A data do evento deve ser uma data válida.',
            'event_time.date_format' => 'A hora do evento deve estar no formato HH:MM.',
            'name.string' => 'O nome do evento deve ser uma string.',
            'name.max' => 'O nome do evento não pode exceder 255 caracteres.',
            'description.string' => 'A descrição do evento deve ser uma string.',
        ];
    }
}
