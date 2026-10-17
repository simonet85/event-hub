<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * Modification d'un événement. Le slug est volontairement absent : il est figé après création
 * pour ne pas casser les liens et QR codes déjà envoyés.
 */
class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()?->can('update', $event) ?? false;
    }

    public function rules(): array
    {
        /** @var Event $event */
        $event = $this->route('event');

        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EventType::class)],
            // Une date inchangée reste valide même passée ; une nouvelle date doit être dans le futur.
            'event_date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, \Closure $fail) use ($event) {
                    try {
                        // Le champ datetime-local n'a pas de secondes : on compare à la minute.
                        $date = Carbon::parse($value)->startOfMinute();
                    } catch (\Throwable) {
                        return; // Valeur non analysable : la règle "date" signale déjà l'erreur.
                    }

                    $unchanged = $event->event_date->copy()->startOfMinute()->equalTo($date);

                    if (! $unchanged && $date->isPast()) {
                        $fail('validation.event_date_future')->translate();
                    }
                },
            ],
            'location_name' => ['nullable', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'welcome_message' => ['nullable', 'string', 'max:2000'],
            'theme_config' => ['nullable', 'array'],
            'theme_config.primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_config.secondary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_config.font_family' => ['nullable', Rule::in(['script', 'serif', 'body'])],
        ];
    }
}
