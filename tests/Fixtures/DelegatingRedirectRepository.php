<?php

namespace Ndx\SimpleRedirect\Tests\Fixtures;

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\Redirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;

class DelegatingRedirectRepository implements RedirectRepository
{
    public function __construct(protected RedirectRepository $repository) {}

    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(string $id): ?Redirect
    {
        return $this->repository->find($id);
    }

    public function enabled(): Collection
    {
        return $this->repository->enabled();
    }

    public function ordered(): Collection
    {
        return $this->repository->ordered();
    }

    public function orderedEnabled(): Collection
    {
        return $this->repository->orderedEnabled();
    }

    public function save(Redirect $redirect): bool
    {
        return $this->repository->save($redirect);
    }

    public function delete(Redirect $redirect): bool
    {
        return $this->repository->delete($redirect);
    }

    public function make(): Redirect
    {
        return $this->repository->make();
    }

    public function reorder(array $ids): void
    {
        $this->repository->reorder($ids);
    }
}
