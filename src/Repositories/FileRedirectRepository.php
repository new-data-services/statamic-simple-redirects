<?php

namespace Ndx\SimpleRedirect\Repositories;

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository as RedirectRepositoryContract;
use Ndx\SimpleRedirect\Contracts\RedirectTreeRepository;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Stache\RedirectsStore;
use Statamic\Stache\Stache;

class FileRedirectRepository implements RedirectRepositoryContract
{
    public function __construct(protected Stache $stache) {}

    protected function store(): RedirectsStore
    {
        return $this->stache->store('redirects');
    }

    protected function treeRepository(): RedirectTreeRepository
    {
        return app(RedirectTreeRepository::class);
    }

    public function all(): Collection
    {
        return $this->store()->getItems(
            $this->store()->paths()->keys()
        );
    }

    public function find(string $id): ?Redirect
    {
        return $this->store()->getItem($id);
    }

    public function enabled(): Collection
    {
        return $this->all()->filter(fn (Redirect $redirect) => $redirect->isEnabled());
    }

    public function ordered(): Collection
    {
        return $this->applyTreeOrder($this->all());
    }

    public function orderedEnabled(): Collection
    {
        return $this->applyTreeOrder($this->enabled());
    }

    protected function applyTreeOrder(Collection $redirects): Collection
    {
        $tree = $this->treeRepository()->findOrCreate('redirects')->tree();

        return collect($tree)
            ->map(fn ($id) => $redirects->firstWhere('id', $id))
            ->filter()
            ->merge($redirects->whereNotIn('id', $tree))
            ->values();
    }

    public function save(Redirect $redirect): bool
    {
        $this->store()->save($redirect);

        $tree = $this->treeRepository()->findOrCreate('redirects');
        $tree->append($redirect->id())->save();

        event(new RedirectSaved($redirect));

        return true;
    }

    public function delete(Redirect $redirect): bool
    {
        $this->store()->delete($redirect);

        $tree = $this->treeRepository()->findOrCreate('redirects');
        $tree->remove($redirect->id())->save();

        event(new RedirectDeleted($redirect));

        return true;
    }

    public function make(): Redirect
    {
        return new RedirectData;
    }
}
