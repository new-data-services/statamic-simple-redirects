<?php

namespace Ndx\SimpleRedirect\Data;

use Illuminate\Contracts\Support\Arrayable;
use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Statamic\Contracts\Data\Augmentable;
use Statamic\Contracts\Data\Augmented;
use Statamic\Data\HasAugmentedInstance;
use Statamic\Facades\Site;
use Statamic\Support\Str;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class Redirect implements Arrayable, Augmentable, RedirectContract
{
    use FluentlyGetsAndSets;
    use HasAugmentedInstance;

    protected ?string $id = null;

    protected ?string $source = null;

    protected ?string $destination = null;

    protected string $type = 'exact';

    protected int $statusCode = 301;

    protected ?string $site = null;

    public function __construct()
    {
        $this->id   = Str::uuid();
        $this->site = Site::default()->handle();
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

    public function site(?string $site = null)
    {
        return $this->fluentlyGetOrSet('site')->args(func_get_args());
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

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'source'      => $this->source,
            'destination' => $this->destination,
            'type'        => $this->type,
            'status_code' => $this->statusCode,
            'site'        => $this->site,
        ];
    }

    public function fileData(): array
    {
        return [
            'source'      => $this->source,
            'destination' => $this->destination,
            'type'        => $this->type,
            'status_code' => $this->statusCode,
            'site'        => $this->site,
        ];
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
