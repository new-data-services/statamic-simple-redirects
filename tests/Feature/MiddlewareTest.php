<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

describe('basic redirect behavior', function () {
    it('redirects 404 requests matching enabled redirect', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-old-page', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/test-old-page')
            ->destination('/new-page')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-old-page')
            ->assertRedirect('/new-page');
    });

    it('does not redirect non-404 responses', function () {
        $redirect = Redirect::make()
            ->source('/existing-page')
            ->destination('/should-not-redirect-here')
            ->enabled(true);

        Redirect::save($redirect);

        $middleware = new HandleRedirects;
        $request    = Request::create('/existing-page', 'GET');
        $okResponse = new Response('Page exists', 200);

        $response = $middleware->handle($request, fn () => $okResponse);

        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('Page exists');
    });

    it('does not redirect when no match found', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-trigger-404', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/some-other-page')
            ->destination('/new-page')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-trigger-404')
            ->assertNotFound();
    });
});

describe('status codes', function () {
    it('uses 301 status code for permanent redirects', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-301-page', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/test-301-page')
            ->destination('/new-page')
            ->statusCode(301)
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-301-page')
            ->assertRedirect('/new-page')
            ->assertStatus(301);
    });

    it('uses 302 status code for temporary redirects', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-302-page', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/test-302-page')
            ->destination('/new-page')
            ->statusCode(302)
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-302-page')
            ->assertRedirect('/new-page')
            ->assertStatus(302);
    });
});

describe('pattern matching', function () {
    it('matches and redirects exact paths', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-exact-page', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/test-exact-page')
            ->destination('/new-page')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-exact-page')->assertRedirect('/new-page');
    });

    it('respects redirect ordering for overlapping patterns', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-order/{slug}', fn () => abort(404));

        $specificRedirect = Redirect::make()
            ->source('/test-order/featured')
            ->destination('/featured-articles')
            ->enabled(true);

        Redirect::save($specificRedirect);

        $generalRedirect = Redirect::make()
            ->source('/test-order/*')
            ->destination('/articles/$1')
            ->enabled(true);

        Redirect::save($generalRedirect);

        Redirect::reorder([$specificRedirect->id(), $generalRedirect->id()]);

        $this->get('/test-order/featured')->assertRedirect('/featured-articles');
    });
});

describe('destination building', function () {
    it('redirects to external url', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-external', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/test-external')
            ->destination('https://example.com/new-page')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-external')->assertRedirect('https://example.com/new-page');
    });

    it('substitutes capture groups in destination', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-capture/{lang}/posts/{slug}', fn () => abort(404));

        $redirect = Redirect::make()
            ->regex(true)
            ->source('/test-capture/([a-z]+)/posts/(.*)')
            ->destination('/articles/$2?lang=$1')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/test-capture/en/posts/hello-world')->assertRedirect('/articles/hello-world?lang=en');
    });
});

describe('enabled/disabled', function () {
    it('ignores disabled redirects', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-disabled', fn () => abort(404));

        $redirect = Redirect::make()
            ->source('/test-disabled')
            ->destination('/new-page')
            ->enabled(false);

        Redirect::save($redirect);

        $this->get('/test-disabled')->assertNotFound();
    });

    it('only processes enabled redirects', function () {
        Route::middleware(['web', HandleRedirects::class])
            ->get('/test-multi', fn () => abort(404));

        $disabledRedirect = Redirect::make()
            ->source('/test-multi')
            ->destination('/wrong-page')
            ->enabled(false);

        Redirect::save($disabledRedirect);

        $enabledRedirect = Redirect::make()
            ->source('/test-multi')
            ->destination('/correct-page')
            ->enabled(true);

        Redirect::save($enabledRedirect);

        $this->get('/test-multi')->assertRedirect('/correct-page');
    });
});
