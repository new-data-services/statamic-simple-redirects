<?php

namespace Ndx\SimpleRedirect\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\ParallelTesting;
use Ndx\SimpleRedirect\ServiceProvider;
use Orchestra\Testbench\Pest\WithPest;
use Statamic\Testing\AddonTestCase;

class TestCase extends AddonTestCase
{
    use WithPest;

    protected string $addonServiceProvider = ServiceProvider::class;

    protected function setUp(): void
    {
        parent::setUp();

        $usersPath = $this->usersPath();

        File::deleteDirectory($usersPath);

        app('stache')->store('users')->directory($usersPath)->clear();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->usersPath());

        parent::tearDown();
    }

    protected function usersPath(): string
    {
        return storage_path("test-users{$this->getSuffix()}");
    }

    protected function getSuffix(): string
    {
        $token = ParallelTesting::token() ?: getenv('TEST_TOKEN') ?: '';

        return $token ? "_{$token}" : '';
    }
}
