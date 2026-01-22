<?php

namespace Ndx\SimpleRedirect\Tests\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Repositories\EloquentRedirectRepository;
use Statamic\Facades\Blink;

trait WithEloquentDriver
{
    use RefreshDatabase;

    protected function setUpWithEloquentDriver(): void
    {
        config()->set('statamic.redirects.driver', 'eloquent');

        $this->runRedirectsMigration();

        app()->singleton(RedirectRepository::class, EloquentRedirectRepository::class);

        Blink::flush();
    }

    protected function runRedirectsMigration(): void
    {
        if (Schema::hasTable('redirects')) {
            return;
        }

        $migration = require __DIR__ . '/../../database/migrations/create_redirects_table.php.stub';
        $migration->up();
    }
}
