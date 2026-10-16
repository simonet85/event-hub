<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('guests', 'email')->where('event_id', $this->route('event')->id),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^\+?[0-9 ().-]{6,}$/'],
            'max_companions' => ['nullable', 'integer', 'min:0', 'max:10'],
        ];
    }
}
