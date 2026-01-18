<?php

namespace Ndx\SimpleRedirect\Contracts;

interface RedirectTreeRepository
{
    public function find(string $handle): ?RedirectTree;

    public function findOrCreate(string $handle): RedirectTree;

    public function save(RedirectTree $tree): bool;
}
