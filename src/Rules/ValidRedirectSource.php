<?php

namespace Ndx\SimpleRedirect\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Ndx\SimpleRedirect\Data\Redirect;

class ValidRedirectSource implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $regex = $this->data['regex'] ?? false;
        $value = trim($value);

        if (! $regex) {
            return;
        }

        $this->validateRegex($value, $fail);
    }

    protected function validateRegex(string $pattern, Closure $fail): void
    {
        $normalizedPattern = Redirect::normalizeRegexPattern($pattern);

        set_error_handler(fn () => true);
        $result = @preg_match($normalizedPattern, '');
        restore_error_handler();

        if ($result === false) {
            $fail('simple-redirects::validation.invalid_regex')->translate();

            return;
        }

        if (preg_match('/(\+|\*|\?)\s*(\+|\*|\?)/', $pattern)) {
            $fail('simple-redirects::validation.dangerous_regex_pattern')->translate();
        }
    }
}
