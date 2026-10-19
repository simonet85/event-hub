<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse les liens dans les textes libres du livre d'or et des légendes : c'est la forme habituelle du spam.
 */
class NoLinks implements ValidationRule
{
    private const PATTERN = '~(https?:|ftp:|www\.|\b[a-z0-9-]+\.(?:com|net|org|ru|xyz|top|info|biz|link|click|shop|online)\b)~i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match(self::PATTERN, $value) === 1) {
            $fail('Les liens ne sont pas autorisés dans les messages.');
        }
    }
}
