<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\Site;

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

describe('multi-site filtering', function () {
    beforeEach(function () {
        config()->set('statamic.system.multisite', true);
    });

    it('applies redirect when site restriction matches current site', function () {
        Site::setCurrent('en');

        $redirect = Redirect::make()
            ->source('/test-site-match')
            ->destination('/new-page')
            ->sites(['en'])
            ->enabled(true);

        Redirect::save($redirect);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeFalse();
    });

    it('skips redirect when site restriction does not match', function () {
        Site::setCurrent('de');

        $redirect = Redirect::make()
            ->source('/test-site-skip')
            ->destination('/new-page')
            ->sites(['en'])
            ->enabled(true);

        Redirect::save($redirect);

        expect($redirect->appliesToSite('de'))->toBeFalse();
    });

    it('applies unrestricted redirect regardless of current site', function () {
        $redirect = Redirect::make()
            ->source('/test-unrestricted')
            ->destination('/new-page')
            ->sites(null)
            ->enabled(true);

        Redirect::save($redirect);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeTrue();
        expect($redirect->appliesToSite('fr'))->toBeTrue();
    });

    it('applies redirect with empty sites to all sites', function () {
        $redirect = Redirect::make()
            ->source('/test-empty-sites')
            ->destination('/new-page')
            ->sites([])
            ->enabled(true);

        Redirect::save($redirect);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeTrue();
    });

    it('applies redirect when current site is in the allowed list', function () {
        $redirect = Redirect::make()
            ->source('/test-multi-allowed')
            ->destination('/new-page')
            ->sites(['en', 'de'])
            ->enabled(true);

        Redirect::save($redirect);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeTrue();
        expect($redirect->appliesToSite('fr'))->toBeFalse();
    });
});

describe('site prefixes', function () {
    beforeEach(function () {
        config()->set('statamic.editions.pro', true);
        config()->set('statamic.system.multisite', true);

        Site::setSites([
            'default' => ['name' => 'Default', 'url' => 'http://localhost/', 'locale' => 'de'],
            'en'      => ['name' => 'English', 'url' => 'http://localhost/en/', 'locale' => 'en'],
        ]);

        Route::middleware(['web', HandleRedirects::class])
            ->any('{any}', fn () => abort(404))
            ->where('any', '.*');
    });

    it('matches a site relative source on a prefixed site', function () {
        $redirect = Redirect::make()
            ->regex(true)
            ->source('/brands/happy-size(.*)')
            ->destination('/brands/happysize$1')
            ->sites(['en'])
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('http://localhost/en/brands/happy-size-xyz')
            ->assertRedirect('/en/brands/happysize-xyz');
    });

    it('matches sources written with the site prefix or the full address', function (string $source) {
        $redirect = Redirect::make()
            ->source($source)
            ->destination('/target')
            ->sites(['en'])
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('http://localhost/en/legacy')->assertRedirect('/en/target');
    })->with([
        'site relative' => ['/legacy'],
        'prefixed'      => ['/en/legacy'],
        'absolute'      => ['http://localhost/en/legacy'],
    ]);

    it('prefixes only destinations that need it', function (string $destination, string $expected) {
        $redirect = Redirect::make()
            ->source('/legacy')
            ->destination($destination)
            ->sites(['en'])
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('http://localhost/en/legacy')->assertRedirect($expected);
    })->with([
        'relative' => [
            '/target',
            '/en/target',
        ],
        'already prefixed' => [
            '/en/target',
            '/en/target',
        ],
        'absolute' => [
            'https://example.com/target',
            'https://example.com/target',
        ],
    ]);

    it('treats the site prefix as a full path segment', function () {
        $redirect = Redirect::make()
            ->source('/enterprise/old')
            ->destination('/enterprise/new')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('http://localhost/enterprise/old')->assertRedirect('/enterprise/new');
    });
});

describe('query strings', function () {
    beforeEach(function () {
        Route::middleware(['web', HandleRedirects::class])
            ->any('{any}', fn () => abort(404))
            ->where('any', '.*');
    });

    it('ignores the query string when matching and carries it over', function () {
        $redirect = Redirect::make()
            ->source('/tracked-old')
            ->destination('/tracked-new')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/tracked-old?utm_source=newsletter&utm_medium=email')
            ->assertRedirect('/tracked-new?utm_source=newsletter&utm_medium=email');
    });

    it('keeps the query string out of wildcard captures', function () {
        $redirect = Redirect::make()
            ->source('/tracked-wildcard/*')
            ->destination('/articles/$1')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/tracked-wildcard/hello?utm_source=newsletter')
            ->assertRedirect('/articles/hello?utm_source=newsletter');
    });

    it('matches a source containing a query string', function () {
        $redirect = Redirect::make()
            ->source('index.php?site=home')
            ->destination('/')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/index.php?site=home')->assertRedirect('/');
    });

    it('does not append the query string to a destination that has its own', function () {
        $redirect = Redirect::make()
            ->source('/own-query-old')
            ->destination('/own-query-new?ref=redirect')
            ->enabled(true);

        Redirect::save($redirect);

        $this->get('/own-query-old?utm_source=newsletter')
            ->assertRedirect('/own-query-new?ref=redirect');
    });
});
