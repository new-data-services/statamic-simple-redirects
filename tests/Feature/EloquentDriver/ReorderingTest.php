<?php

use Illuminate\Support\Facades\DB;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithEloquentDriver;

uses(WithEloquentDriver::class);

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

it('updates order column in database', function () {
    $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
    $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
    $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

    Redirect::save($redirect1);
    Redirect::save($redirect2);
    Redirect::save($redirect3);

    Redirect::reorder([$redirect3->id(), $redirect1->id(), $redirect2->id()]);

    $this->assertDatabaseHas('redirects', ['id' => $redirect3->id(), 'order' => 0]);
    $this->assertDatabaseHas('redirects', ['id' => $redirect1->id(), 'order' => 1]);
    $this->assertDatabaseHas('redirects', ['id' => $redirect2->id(), 'order' => 2]);
});

it('handles partial reorder array', function () {
    $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
    $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
    $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

    Redirect::save($redirect1);
    Redirect::save($redirect2);
    Redirect::save($redirect3);

    Redirect::reorder([$redirect2->id()]);

    $this->assertDatabaseHas('redirects', ['id' => $redirect2->id(), 'order' => 0]);
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

it('only updates redirects whose order changed', function () {
    $ids = collect(range(1, 4))->map(function (int $number) {
        $redirect = Redirect::make()->source("/page-{$number}")->destination("/new-{$number}");

        Redirect::save($redirect);

        return $redirect->id();
    })->all();

    DB::enableQueryLog();

    Redirect::reorder([$ids[0], $ids[2], $ids[1], $ids[3]]);

    $updates = collect(DB::getQueryLog())->filter(fn (array $query) => str_starts_with(strtolower($query['query']), 'update'));

    expect($updates)->toHaveCount(2)
        ->and(Redirect::ordered()->map->id()->values()->all())->toBe([$ids[0], $ids[2], $ids[1], $ids[3]]);
});
