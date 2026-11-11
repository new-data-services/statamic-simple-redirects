<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Events\RedirectMatched;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Tests\Concerns\WithEloquentDriver;

use function Illuminate\Events\queueable;

uses(WithEloquentDriver::class);

it('works with queued listeners', function () {
    Route::middleware(['web', HandleRedirects::class])
        ->get('/old', fn () => abort(404));

    Event::listen(queueable(function (RedirectMatched $event) {
        Cache::put('matched-redirect', [$event->redirect->id(), $event->matchedUrl, $event->destination]);
    }));

    $redirect = Redirect::make()->source('/old')->destination('/new');
    Redirect::save($redirect);

    $this->get('/old')->assertRedirect('/new');

    expect(Cache::get('matched-redirect'))->toBe([$redirect->id(), '/old', '/new']);
});
