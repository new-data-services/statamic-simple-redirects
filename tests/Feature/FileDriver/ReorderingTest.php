<?php

use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

it('reorders redirects via repository', function () {
    $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
    $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
    $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

    Redirect::save($redirect1);
    Redirect::save($redirect2);
    Redirect::save($redirect3);

    Redirect::reorder([$redirect3->id(), $redirect1->id(), $redirect2->id()]);

    $ordered = Redirect::ordered();

    expect($ordered->values()->pluck('id')->all())->toBe([
        $redirect3->id(),
        $redirect1->id(),
        $redirect2->id(),
    ]);
});

it('applies tree order when fetching ordered redirects', function () {
    $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
    $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');

    Redirect::save($redirect1);
    Redirect::save($redirect2);

    Redirect::reorder([$redirect2->id(), $redirect1->id()]);

    $ordered = Redirect::ordered();

    expect($ordered->first()->source())->toBe('/page-2');
    expect($ordered->last()->source())->toBe('/page-1');
});

it('handles redirects not in tree gracefully', function () {
    $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
    $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
    $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

    Redirect::save($redirect1);
    Redirect::save($redirect2);
    Redirect::save($redirect3);

    Redirect::reorder([$redirect2->id()]);

    $ordered = Redirect::ordered();

    expect($ordered)->toHaveCount(3);
    expect($ordered->first()->id())->toBe($redirect2->id());
});

it('clears cache after reorder', function () {
    $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1')->enabled(true);
    $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2')->enabled(true);

    Redirect::save($redirect1);
    Redirect::save($redirect2);

    Redirect::orderedEnabled();

    Redirect::reorder([$redirect2->id(), $redirect1->id()]);

    $orderedEnabled = Redirect::orderedEnabled();

    expect($orderedEnabled->first()->id())->toBe($redirect2->id());
});
