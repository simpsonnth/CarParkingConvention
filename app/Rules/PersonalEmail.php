<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PersonalEmail implements ValidationRule
{
    /** @var list<string> */
    public const BLOCKED_DOMAIN_SUFFIXES = [
        'jwpub.org',
        'jw.org',
        'bethel.jw.org',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $email = strtolower(trim($value));
        $atPos = strrpos($email, '@');
        if ($atPos === false) {
            return;
        }

        $domain = substr($email, $atPos + 1);
        if ($domain === '') {
            return;
        }

        foreach (self::BLOCKED_DOMAIN_SUFFIXES as $suffix) {
            if ($domain === $suffix || str_ends_with($domain, '.'.$suffix)) {
                $fail(__('ticket_change_request.validation.personal_email'));

                return;
            }
        }
    }
}
