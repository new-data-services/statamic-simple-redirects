<?php

namespace Ndx\SimpleRedirect\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRedirectDestination implements ValidationRule
{
    protected array $blockedProtocols = [
        'javascript:',
        'data:',
        'vbscript:',
        'file:',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = trim($value);

        foreach ($this->blockedProtocols as $protocol) {
            if (stripos($value, $protocol) === 0) {
                $fail('simple-redirects::messages.validation.blocked_protocol')->translate();

                return;
            }
        }
    }
}
