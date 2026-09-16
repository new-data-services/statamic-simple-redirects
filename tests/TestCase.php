<?php

namespace Ndx\SimpleRedirect\Tests;

use Ndx\SimpleRedirect\ServiceProvider;
use Orchestra\Testbench\Pest\WithPest;
use Statamic\Testing\AddonTestCase;

class TestCase extends AddonTestCase
{
    use WithPest;

    protected string $addonServiceProvider = ServiceProvider::class;
}
