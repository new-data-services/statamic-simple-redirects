<?php

namespace Ndx\SimpleRedirect\Tests;

use Ndx\SimpleRedirect\ServiceProvider;
use Statamic\Testing\AddonTestCase;

class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = ServiceProvider::class;
}
