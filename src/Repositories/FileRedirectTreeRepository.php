<?php

namespace Ndx\SimpleRedirect\Repositories;

use Ndx\SimpleRedirect\Contracts\RedirectTree;
use Ndx\SimpleRedirect\Contracts\RedirectTreeRepository as RedirectTreeRepositoryContract;
use Ndx\SimpleRedirect\Data\RedirectTree as RedirectTreeData;
use Ndx\SimpleRedirect\Stache\RedirectsTreeStore;
use Statamic\Stache\Stache;

class FileRedirectTreeRepository implements RedirectTreeRepositoryContract
{
    public function __construct(protected Stache $stache) {}

    protected function store(): RedirectsTreeStore
    {
        return $this->stache->store('redirects-tree');
    }

    public function find(string $handle): ?RedirectTree
    {
        return $this->store()->getItem($handle);
    }

    public function findOrCreate(string $handle): RedirectTree
    {
        return $this->find($handle) ?? (new RedirectTreeData)->handle($handle);
    }

    public function save(RedirectTree $tree): bool
    {
        $this->store()->save($tree);

        return true;
    }
}
