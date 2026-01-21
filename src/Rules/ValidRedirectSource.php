<?php

namespace Ndx\SimpleRedirect\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

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
        $normalizedPattern = $this->normalizeRegexPattern($pattern);

        set_error_handler(fn () => true);
        $result = @preg_match($normalizedPattern, '');
        restore_error_handler();

        if ($result === false) {
            $fail('simple-redirects::messages.validation.invalid_regex')->translate();

            return;
        }

        if (preg_match('/(\+|\*|\?)\s*(\+|\*|\?)/', $pattern)) {
            $fail('simple-redirects::messages.validation.dangerous_regex_pattern')->translate();
        }
    }

    protected function normalizeRegexPattern(string $pattern): string
    {
        if (preg_match('/^[#\/~@!%].*[#\/~@!%][imsxADSUXu]*$/', $pattern)) {
            return $pattern;
        }

        if (! str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        return '#^' . $pattern . '$#';
    }
}
