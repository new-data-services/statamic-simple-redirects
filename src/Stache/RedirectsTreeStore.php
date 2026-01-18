<?php

namespace Ndx\SimpleRedirect\Stache;

use Ndx\SimpleRedirect\Data\RedirectTree;
use Statamic\Facades\YAML;
use Statamic\Stache\Stores\BasicStore;
use Symfony\Component\Finder\SplFileInfo;

class RedirectsTreeStore extends BasicStore
{
    protected $defaultIndexes = ['path'];

    public function key(): string
    {
        return 'redirects-tree';
    }

    public function getItemFilter(SplFileInfo $file): bool
    {
        return $file->getExtension() === 'yaml';
    }

    public function makeItemFromFile($path, $contents): RedirectTree
    {
        $handle = $this->parseHandle($path);
        $data   = YAML::file($path)->parse($contents);

        return (new RedirectTree)
            ->initialPath($path)
            ->handle($handle)
            ->tree($data['tree'] ?? []);
    }

    protected function parseHandle(string $path): string
    {
        $relativePath = str_replace($this->directory(), '', $path);

        return pathinfo(ltrim($relativePath, '/'), PATHINFO_FILENAME);
    }

    public function getItemKey($item): string
    {
        return $item->handle();
    }
}
