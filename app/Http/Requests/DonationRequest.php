<?php

namespace App\Http\Requests;

use App\Models\Guest;
use App\Rules\NoLinks;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DonationRequest extends FormRequest
{
    public const MIN_AMOUNT = 100;

    public const MAX_AMOUNT = 5_000_000;

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
            'amount' => ['required', 'integer', 'min:'.self::MIN_AMOUNT, 'max:'.self::MAX_AMOUNT],
            'gift_id' => ['nullable', 'integer', Rule::exists('gifts', 'id')->where('event_id', $guest->event_id)],
            'message' => ['nullable', 'string', 'max:500', new NoLinks],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Le montant minimum est de '.self::MIN_AMOUNT.' FCFA.',
            'amount.max' => 'Le montant maximum est de '.number_format(self::MAX_AMOUNT, 0, ',', ' ').' FCFA.',
            'amount.integer' => 'Le montant doit être un nombre entier de FCFA.',
        ];
    }
}
