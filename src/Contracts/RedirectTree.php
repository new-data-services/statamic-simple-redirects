<?php

namespace Ndx\SimpleRedirect\Contracts;

interface RedirectTree
{
    public function handle(?string $handle = null);

    public function tree(?array $tree = null);

    public function append(string $id): self;

    public function remove(string $id): self;

    public function move(string $id, int $position): self;

    public function path(): string;

    public function initialPath(?string $path = null);

    public function fileData(): array;

    public function save(): bool;
}
