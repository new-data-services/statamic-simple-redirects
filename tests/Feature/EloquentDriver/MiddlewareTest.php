<?php

use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Tests\Concerns\WithEloquentDriver;

uses(WithEloquentDriver::class);

beforeEach(function () {
    Route::middleware(['web', HandleRedirects::class])
        ->any('{any}', fn () => abort(404))
        ->where('any', '.*');
});

it('matches exact sources case insensitively', function () {
    Redirect::save(Redirect::make()->source('/Old-Page')->destination('/new-page'));

    $this->get('/old-page')->assertRedirect('/new-page');
});

it('respects redirect ordering between wildcard and exact sources', function () {
    Redirect::save(Redirect::make()->source('/shop/*')->destination('/wildcard'));
    Redirect::save(Redirect::make()->source('/shop/item')->destination('/exact'));

    $this->get('/shop/item')->assertRedirect('/wildcard');
});

it('matches sources given as absolute url', function () {
    Redirect::save(Redirect::make()->source('http://localhost/old')->destination('/new'));

    $this->get('/old')->assertRedirect('/new');
});

it('matches a source containing a query string', function () {
    Redirect::save(Redirect::make()->source('/old?ref=mail')->destination('/new'));

    $this->get('/old?ref=mail')->assertRedirect('/new');
});

it('ignores disabled exact redirects', function () {
    Redirect::save(Redirect::make()->source('/old')->destination('/new')->enabled(false));

    $this->get('/old')->assertNotFound();
});
