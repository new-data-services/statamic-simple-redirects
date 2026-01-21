<?php

namespace Ndx\SimpleRedirect\Data;

use Illuminate\Contracts\Support\Arrayable;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Augmented;
use Statamic\Data\ExistsAsFile;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Facades\Stache;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Redirect implements Arrayable, Augmentable, RedirectContract
{
    use ExistsAsFile, FluentlyGetsAndSets, HasAugmentedInstance;

    protected ?string $id = null;

    protected ?string $source = null;

    protected ?string $destination = null;

    protected bool $regex = false;

    protected int $statusCode = 301;

    protected bool $enabled = true;

    protected ?string $compiledPattern = null;

    public function __construct()
    {
        $this->id = (string) Str::uuid();
    }

    public function id(?string $id = null)
    {
        return $this->fluentlyGetOrSet('id')->args(func_get_args());
    }

    public function source(?string $source = null)
    {
        return $this->fluentlyGetOrSet('source')
            ->setter(function ($value) {
                $this->compiledPattern = null;

                return static::normalizeSource($value, $this->regex);
            })
            ->args(func_get_args());
    }

    public function destination(?string $destination = null)
    {
        return $this->fluentlyGetOrSet('destination')->args(func_get_args());
    }

    public function regex(?bool $regex = null)
    {
        return $this->fluentlyGetOrSet('regex')
            ->setter(function ($value) {
                $this->compiledPattern = null;
                if ($this->source) {
                    $this->source = static::normalizeSource($this->source, $value);
                }

                return $value;
            })
            ->args(func_get_args());
    }

    public function statusCode(?int $statusCode = null)
    {
        return $this->fluentlyGetOrSet('statusCode')->args(func_get_args());
    }

    public function enabled(?bool $enabled = null)
    {
        return $this->fluentlyGetOrSet('enabled')->args(func_get_args());
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isRegex(): bool
    {
        return $this->regex;
    }

    public function matches(string $url): bool
    {
        $pattern = $this->getCompiledPattern();

        return (bool) @preg_match($pattern, $url);
    }

    public function buildDestination(string $matchedUrl): string
    {
        return $this->replaceCaptures($matchedUrl);
    }

    protected function replaceCaptures(string $url): string
    {
        $pattern = $this->getCompiledPattern();

        if (@preg_match($pattern, $url, $matches)) {
            return preg_replace_callback(
                '/\$(\d+)/',
                function ($match) use ($matches) {
                    $index = (int) $match[1];

                    if (! isset($matches[$index])) {
                        return $match[0];
                    }

                    return $matches[$index];
                },
                $this->destination
            );
        }

        return $this->destination;
    }

    protected function getCompiledPattern(): string
    {
        if ($this->compiledPattern === null) {
            $this->compiledPattern = $this->regex
                ? $this->compileRegexPattern($this->source)
                : $this->compileWildcardPattern($this->source);
        }

        return $this->compiledPattern;
    }

    protected function compileWildcardPattern(string $pattern): string
    {
        $escaped = preg_quote($pattern, '#');

        $escaped = str_replace('\\*', '(.*)', $escaped);

        return '#^' . $escaped . '$#i';
    }

    protected function compileRegexPattern(string $pattern): string
    {
        if (preg_match('/^[#\/~@!%].*[#\/~@!%][imsxADSUXu]*$/', $pattern)) {
            return $pattern;
        }

        if (! str_starts_with($pattern, '/')) {
            $pattern = '/' . $pattern;
        }

        return '#^' . $pattern . '$#';
    }

    public static function normalizeSource(string $source, bool $regex): string
    {
        if (! $regex) {
            if (! str_starts_with($source, '/')) {
                $source = '/' . $source;
            }
        }

        return $source;
    }

    public function path(): string
    {
        return vsprintf('%s/%s.md', [
            rtrim(Stache::store('redirects')->directory(), '/'),
            $this->id,
        ]);
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'source'      => $this->source,
            'destination' => $this->destination,
            'regex'       => $this->regex,
            'status_code' => $this->statusCode,
            'enabled'     => $this->enabled,
        ];
    }

    public function fileData(): array
    {
        $data = [
            'source'      => $this->source,
            'destination' => $this->destination,
            'status_code' => $this->statusCode,
        ];

        if ($this->regex) {
            $data['regex'] = true;
        }

        if (! $this->enabled) {
            $data['enabled'] = false;
        }

        return $data;
    }

    public function augmentedArrayData(): array
    {
        return $this->toArray();
    }

    public function newAugmentedInstance(): Augmented
    {
        return new AugmentedRedirect($this);
    }
}
