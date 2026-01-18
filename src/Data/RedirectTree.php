<?php

namespace Ndx\SimpleRedirect\Data;

use Ndx\SimpleRedirect\Contracts\RedirectTree as RedirectTreeContract;
use Ndx\SimpleRedirect\Contracts\RedirectTreeRepository;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Statamic\Data\ExistsAsFile;
use Statamic\Facades\Stache;
use Statamic\Support\Traits\FluentlyGetsAndSets;

class RedirectTree implements RedirectTreeContract
{
    use ExistsAsFile, FluentlyGetsAndSets;

    protected string $handle = 'redirects';

    protected array $tree = [];

    public function handle(?string $handle = null)
    {
        return $this->fluentlyGetOrSet('handle')->args(func_get_args());
    }

    public function tree(?array $tree = null)
    {
        return $this->fluentlyGetOrSet('tree')->args(func_get_args());
    }

    public function append(string $id): self
    {
        if (! in_array($id, $this->tree)) {
            $this->tree[] = $id;
        }

        return $this;
    }

    public function remove(string $id): self
    {
        $this->tree = array_values(
            array_filter($this->tree, fn ($item) => $item !== $id)
        );

        return $this;
    }

    public function move(string $id, int $position): self
    {
        $this->remove($id);

        array_splice($this->tree, $position, 0, [$id]);

        return $this;
    }

    public function path(): string
    {
        return vsprintf('%s/%s.yaml', [
            rtrim(Stache::store('redirects-tree')->directory(), '/'),
            $this->handle,
        ]);
    }

    public function fileData(): array
    {
        return [
            'tree' => $this->tree,
        ];
    }

    public function save(): bool
    {
        $this->repository()->save($this);

        event(new RedirectTreeSaved($this));

        return true;
    }

    protected function repository(): RedirectTreeRepository
    {
        return app(RedirectTreeRepository::class);
    }
}
