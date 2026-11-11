<?php

namespace Ndx\SimpleRedirect\Tests;

use Ndx\SimpleRedirect\ServiceProvider;
use Orchestra\Testbench\Pest\WithPest;
use Statamic\Testing\AddonTestCase;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class TestCase extends AddonTestCase
{
    use PreventsSavingStacheItemsToDisk;
    use WithPest;

    protected string $addonServiceProvider = ServiceProvider::class;
}
