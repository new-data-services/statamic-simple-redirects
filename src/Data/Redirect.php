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

    protected string $type = 'exact';

    protected int $statusCode = 301;

    protected bool $enabled = true;

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
        return $this->fluentlyGetOrSet('source')->args(func_get_args());
    }

    public function destination(?string $destination = null)
    {
        return $this->fluentlyGetOrSet('destination')->args(func_get_args());
    }

    public function type(?string $type = null)
    {
        return $this->fluentlyGetOrSet('type')->args(func_get_args());
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

    public function isExact(): bool
    {
        return $this->type === 'exact';
    }

    public function isRegex(): bool
    {
        return $this->type === 'regex';
    }

    public function matches(string $url): bool
    {
        if ($this->isExact()) {
            return $this->source === $url;
        }

        if ($this->isRegex()) {
            return (bool) preg_match($this->source, $url);
        }

        return false;
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
            'type'        => $this->type,
            'status_code' => $this->statusCode,
            'enabled'     => $this->enabled,
        ];
    }

    public function fileData(): array
    {
        $data = [
            'source'      => $this->source,
            'destination' => $this->destination,
            'type'        => $this->type,
            'status_code' => $this->statusCode,
        ];

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
