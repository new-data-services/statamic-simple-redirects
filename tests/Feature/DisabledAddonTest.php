<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Statamic\Facades\Permission;

use function Orchestra\Testbench\Pest\defineEnvironment;

defineEnvironment(function ($app) {
    $app['config']->set('statamic.redirects.enabled', false);
});

describe('disabled addon', function () {
    it('does not register the middleware in the web group', function () {
        $middleware = app(Router::class)->getMiddlewareGroups()['web'];

        expect($middleware)->not->toContain(HandleRedirects::class);
    });

    it('does not register the permission', function () {
        expect(Permission::get('manage redirects'))->toBeNull();
    });

    it('leaves 404 responses untouched', function () {
        Route::middleware('web')->get('/disabled-404', fn () => abort(404));

        $this->get('/disabled-404')->assertNotFound();
    });
});
