<?php

namespace Ndx\SimpleRedirect\Stache;

use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Symfony\Component\Finder\SplFileInfo;

class RedirectStore extends BasicStore
{
    public function key(): string
    {
        return 'redirects';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        return $file->getExtension() === 'md';
    }

    public function makeItemFromFile($path, $contents): RedirectData
    {
        $data = YAML::parse($contents);
        $id   = pathinfo($path, PATHINFO_FILENAME);

        return (new RedirectData)
            ->id($id)
            ->initialPath($path)
            ->source($data['source'] ?? null)
            ->destination($data['destination'] ?? null)
            ->type($data['type'] ?? 'exact')
            ->statusCode($data['status_code'] ?? 301)
            ->enabled($data['enabled'] ?? true);
    }
}
