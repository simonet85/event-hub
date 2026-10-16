<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (filled($this->slug)) {
            $this->merge(['slug' => Str::slug($this->slug)]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('events', 'slug')],
            'type' => ['required', Rule::enum(EventType::class)],
            'event_date' => ['required', 'date', 'after:now'],
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
