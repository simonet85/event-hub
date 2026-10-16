<?php

namespace App\Http\Requests;

use App\Enums\RsvpStatus;
use App\Models\Guest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Guest $guest */
        $guest = $this->route('guest');

        return [
            'rsvp_status' => ['required', Rule::in([RsvpStatus::Accepted->value, RsvpStatus::Declined->value])],
            'companions_count' => ['nullable', 'integer', 'min:0', 'max:'.$guest->max_companions],
            'dietary_restrictions' => ['nullable', 'string', 'max:500'],
            'favorite_song' => ['nullable', 'string', 'max:255'],
            'rsvp_message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
