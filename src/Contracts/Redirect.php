<?php

namespace Ndx\SimpleRedirect\Contracts;

interface Redirect
{
    public function id(?string $id = null);

    public function source(?string $source = null);

    public function destination(?string $destination = null);

    public function regex(?bool $regex = null);

    public function statusCode(?int $statusCode = null);

    public function enabled(?bool $enabled = null);

    public function isEnabled(): bool;

    public function isRegex(): bool;

    public function matches(string $url): bool;

    public function buildDestination(string $matchedUrl): string;

    public function toArray(): array;

    public function fileData(): array;

    public function path(): string;

    public function initialPath(?string $path = null);
}
