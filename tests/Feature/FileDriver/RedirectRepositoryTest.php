<?php

use Illuminate\Support\Facades\File;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

describe('CRUD operations', function () {
    it('creates new redirect via make', function () {
        $redirect = Redirect::make();

        expect($redirect)->toBeInstanceOf(RedirectData::class);
        expect($redirect->id())->not->toBeNull();
    });

    it('saves redirect to file system', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');

        Redirect::save($redirect);

        expect(File::exists($redirect->path()))->toBeTrue();
    });

    it('finds redirect by id', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');
        Redirect::save($redirect);

        $found = Redirect::find($redirect->id());

        expect($found)->not->toBeNull();
        expect($found->id())->toBe($redirect->id());
        expect($found->source())->toBe('/old-page');
        expect($found->destination())->toBe('/new-page');
    });

    it('returns null when redirect not found', function () {
        $found = Redirect::find('non-existent-id');

        expect($found)->toBeNull();
    });

    it('updates existing redirect', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');
        Redirect::save($redirect);

        $redirect->destination('/updated-page');
        Redirect::save($redirect);

        $found = Redirect::find($redirect->id());
        expect($found->destination())->toBe('/updated-page');
    });

});

describe('file-specific operations', function () {
    it('saves redirect to file system', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');

        Redirect::save($redirect);

        expect(File::exists($redirect->path()))->toBeTrue();
    });

});

describe('tree integration', function () {
    it('appends to tree when saving new redirect', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');

        Redirect::save($redirect1);
        Redirect::save($redirect2);

        $ordered = Redirect::ordered();

        expect($ordered)->toHaveCount(2);
        expect($ordered->first()->id())->toBe($redirect1->id());
        expect($ordered->last()->id())->toBe($redirect2->id());
    });

    it('removes from tree when deleting', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');

        Redirect::save($redirect1);
        Redirect::save($redirect2);
        Redirect::delete($redirect1);

        $ordered = Redirect::ordered();

        expect($ordered)->toHaveCount(1);
        expect($ordered->first()->id())->toBe($redirect2->id());
    });
});

describe('blink cache', function () {
    it('clears cache after save', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1')->enabled(true);
        Redirect::save($redirect1);

        Redirect::orderedEnabled();

        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2')->enabled(true);
        Redirect::save($redirect2);

        $orderedEnabled = Redirect::orderedEnabled();

        expect($orderedEnabled)->toHaveCount(2);
    });

    it('clears cache after delete', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1')->enabled(true);
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2')->enabled(true);
        Redirect::save($redirect1);
        Redirect::save($redirect2);

        Redirect::orderedEnabled();

        Redirect::delete($redirect1);

        $orderedEnabled = Redirect::orderedEnabled();

        expect($orderedEnabled)->toHaveCount(1);
    });
});
