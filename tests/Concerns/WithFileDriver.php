<?php

namespace Ndx\SimpleRedirect\Tests\Concerns;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;
use Statamic\Facades\Blink;

trait WithFileDriver
{
    protected function setUpWithFileDriver(): void
    {
        config()->set('statamic.redirects.driver', 'file');

        $suffix = $this->getSuffix();

        $redirectsPath = storage_path("test-redirects{$suffix}");
        $treePath      = storage_path("test-redirects-tree{$suffix}");

        config()->set('statamic.redirects.stores.redirects', $redirectsPath);
        config()->set('statamic.redirects.stores.redirects-tree', $treePath);

        File::deleteDirectory($redirectsPath);
        File::deleteDirectory($treePath);

        $stache = app('stache');

        $stache->store('redirects')->directory($redirectsPath);
        $stache->store('redirects-tree')->directory($treePath);

        $stache->store('redirects')->clear();
        $stache->store('redirects-tree')->clear();

        Blink::flush();
    }

    protected function tearDownWithFileDriver(): void
    {
        $suffix = $this->getSuffix();

        File::deleteDirectory(storage_path("test-redirects{$suffix}"));
        File::deleteDirectory(storage_path("test-redirects-tree{$suffix}"));
    }

    protected function getSuffix(): string
    {
        $token = ParallelTesting::token() ?: getenv('TEST_TOKEN') ?: '';

        return $token ? "_{$token}" : '';
    }
}
