<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class ScheduleItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function messages(): array
    {
        return [
            'starts_at.after_or_equal' => 'L\'étape doit commencer au plus tôt la veille de l\'événement.',
            'starts_at.before_or_equal' => 'L\'étape doit commencer au plus tard 2 jours après l\'événement.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'starts_at' => [
                'required', 'date',
                'after_or_equal:'.$event->event_date->copy()->subDay()->toDateTimeString(),
                'before_or_equal:'.$event->event_date->copy()->addDays(2)->toDateTimeString(),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
