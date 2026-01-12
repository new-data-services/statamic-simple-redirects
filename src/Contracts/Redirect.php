<?php

namespace Ndx\SimpleRedirect\Contracts;

interface Redirect
{
    public function id(?string $id = null);

    public function source(?string $source = null);

    public function destination(?string $destination = null);

    public function type(?string $type = null);

    public function statusCode(?int $statusCode = null);

    public function site(?string $site = null);

    public function isExact(): bool;

    public function isRegex(): bool;

    public function matches(string $url): bool;

    public function toArray(): array;

    public function fileData(): array;
}
