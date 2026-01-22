<?php

namespace Ndx\SimpleRedirect\Tests\Concerns;

use Illuminate\Support\Facades\File;
use Statamic\Facades\Blink;

trait WithFileDriver
{
    protected function setUpWithFileDriver(): void
    {
        config()->set('statamic.redirects.driver', 'file');

        File::deleteDirectory(config('statamic.redirects.stores.redirects'));
        File::deleteDirectory(config('statamic.redirects.stores.redirects-tree'));

        Blink::flush();
    }
}
