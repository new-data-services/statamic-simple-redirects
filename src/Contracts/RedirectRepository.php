<?php

namespace Ndx\SimpleRedirect\Contracts;

use Illuminate\Support\Collection;

interface RedirectRepository
{
    public function all(): Collection;

    public function find(string $id): ?Redirect;

    public function findBySite(string $site): Collection;

    public function save(Redirect $redirect): bool;

    public function delete(Redirect $redirect): bool;

    public function make(): Redirect;
}
