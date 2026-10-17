<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GiftRequest extends FormRequest
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
            'description' => ['nullable', 'string', 'max:1000'],
            'target_amount' => ['required', 'integer', 'min:'.DonationRequest::MIN_AMOUNT, 'max:'.DonationRequest::MAX_AMOUNT],
        ];
    }
}
