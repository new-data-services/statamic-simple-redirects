<?php

namespace Ndx\SimpleRedirect\Contracts;

use Illuminate\Support\Collection;

interface RedirectRepository
{
    public function all(): Collection;

    public function find(string $id): ?Redirect;

    public function enabled(): Collection;

    public function ordered(): Collection;

    public function orderedEnabled(): Collection;

    public function save(Redirect $redirect): bool;

    public function delete(Redirect $redirect): bool;

    public function make(): Redirect;
}
