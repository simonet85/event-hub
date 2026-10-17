<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CagnotteSettingsRequest extends FormRequest
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
            'cagnotte_enabled' => ['required', 'boolean'],
            'cagnotte_goal' => ['nullable', 'integer', 'min:'.DonationRequest::MIN_AMOUNT, 'max:100000000'],
            'cagnotte_message' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
