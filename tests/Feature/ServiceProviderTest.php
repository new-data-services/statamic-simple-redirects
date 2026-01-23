<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Actions\DeleteRedirect;
use Ndx\SimpleRedirect\Actions\DisableRedirect;
use Ndx\SimpleRedirect\Actions\EnableRedirect;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Contracts\RedirectTreeRepository;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Repositories\EloquentRedirectRepository;
use Ndx\SimpleRedirect\Repositories\FileRedirectRepository;
use Ndx\SimpleRedirect\Repositories\FileRedirectTreeRepository;
use Statamic\Facades\Git;
use Statamic\Facades\Permission;

describe('feature flag', function () {
    it('boots addon when enabled config is true', function () {
        config()->set('statamic.redirects.enabled', true);

        $permission = Permission::get('manage redirects');

        expect($permission)->not->toBeNull();
    });
});

describe('driver registration', function () {
    it('registers FileRedirectRepository when driver is file', function () {
        config()->set('statamic.redirects.driver', 'file');

        $repository = app(RedirectRepository::class);

        expect($repository)->toBeInstanceOf(FileRedirectRepository::class);
    });

    it('registers EloquentRedirectRepository when driver is eloquent', function () {
        config()->set('statamic.redirects.driver', 'eloquent');

        app()->singleton(RedirectRepository::class, EloquentRedirectRepository::class);
        $repository = app(RedirectRepository::class);

        expect($repository)->toBeInstanceOf(EloquentRedirectRepository::class);
    });

    it('registers FileRedirectTreeRepository for file driver', function () {
        config()->set('statamic.redirects.driver', 'file');

        $repository = app(RedirectTreeRepository::class);

        expect($repository)->toBeInstanceOf(FileRedirectTreeRepository::class);
    });
});

describe('permissions', function () {
    it('registers manage redirects permission', function () {
        $permission = Permission::get('manage redirects');

        expect($permission)->not->toBeNull();
    });
});

describe('actions', function () {
    it('registers EnableRedirect action', function () {
        $actions       = app('statamic.actions')->all();
        $actionClasses = collect($actions)->map(fn ($a) => is_object($a) ? get_class($a) : $a)->values()->all();

        expect($actionClasses)->toContain(EnableRedirect::class);
    });

    it('registers DisableRedirect action', function () {
        $actions       = app('statamic.actions')->all();
        $actionClasses = collect($actions)->map(fn ($a) => is_object($a) ? get_class($a) : $a)->values()->all();

        expect($actionClasses)->toContain(DisableRedirect::class);
    });

    it('registers DeleteRedirect action', function () {
        $actions       = app('statamic.actions')->all();
        $actionClasses = collect($actions)->map(fn ($a) => is_object($a) ? get_class($a) : $a)->values()->all();

        expect($actionClasses)->toContain(DeleteRedirect::class);
    });
});

describe('stache stores', function () {
    it('registers redirects stache store for file driver', function () {
        config()->set('statamic.redirects.driver', 'file');

        $stache = app('stache');

        expect($stache->stores()->has('redirects'))->toBeTrue();
    });

    it('registers redirects-tree stache store for file driver', function () {
        config()->set('statamic.redirects.driver', 'file');

        $stache = app('stache');

        expect($stache->stores()->has('redirects-tree'))->toBeTrue();
    });
});

describe('routes', function () {
    it('registers control panel routes', function () {
        $routes = collect(Route::getRoutes())
            ->filter(fn ($r) => str_starts_with($r->getName() ?? '', 'statamic.cp.simple-redirects.'));

        expect($routes->count())->toBeGreaterThan(0);
    });
});

describe('middleware', function () {
    it('registers HandleRedirects middleware in web group', function () {
        $middleware = app(Router::class)
            ->getMiddlewareGroups()['web'];

        expect($middleware)->toContain(HandleRedirects::class);
    });
});

describe('git integration', function () {
    it('registers git listeners for redirect events when git is enabled', function () {
        config()->set('statamic.git.enabled', true);

        Git::shouldReceive('listen')->with(RedirectSaved::class)->once();
        Git::shouldReceive('listen')->with(RedirectDeleted::class)->once();
        Git::shouldReceive('listen')->with(RedirectTreeSaved::class)->once();

        $provider   = new \Ndx\SimpleRedirect\ServiceProvider(app());
        $reflection = new \ReflectionMethod($provider, 'bootGitListeners');
        $reflection->invoke($provider);
    });
});
