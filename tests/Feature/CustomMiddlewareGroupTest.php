<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

use function Orchestra\Testbench\Pest\defineEnvironment;

uses(WithFileDriver::class);

defineEnvironment(function ($app) {
    $app['config']->set('statamic.routes.middleware', 'frontend');
});

describe('custom middleware group', function () {
    it('registers the middleware in the configured group instead of web', function () {
        $groups = app(Router::class)->getMiddlewareGroups();

        expect($groups['frontend'])->toContain(HandleRedirects::class)
            ->and($groups['web'])->not->toContain(HandleRedirects::class);
    });

    it('redirects 404 responses from routes in the configured group', function () {
        Route::middleware('frontend')->get('/old-page', fn () => abort(404));

        Redirect::save(
            Redirect::make()->source('/old-page')->destination('/new-page')->enabled(true)
        );

        $this->get('/old-page')->assertRedirect('/new-page');
    });
});
