<?php

use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithEloquentDriver;
use Statamic\Facades\Blink;

uses(WithEloquentDriver::class);

describe('CRUD operations', function () {
    it('creates new redirect via make', function () {
        $redirect = Redirect::make();

        expect($redirect)->toBeInstanceOf(RedirectData::class);
        expect($redirect->id())->not->toBeNull();
    });

    it('saves redirect to database', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');

        Redirect::save($redirect);

        $this->assertDatabaseHas('redirects', [
            'id'          => $redirect->id(),
            'source'      => '/old-page',
            'destination' => '/new-page',
        ]);
    });

    it('finds redirect by id', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');
        Redirect::save($redirect);

        Blink::flush();

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

        $this->assertDatabaseHas('redirects', [
            'id'          => $redirect->id(),
            'destination' => '/updated-page',
        ]);
    });

    it('deletes redirect from database', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');
        Redirect::save($redirect);

        $this->assertDatabaseHas('redirects', ['id' => $redirect->id()]);

        Redirect::delete($redirect);

        $this->assertDatabaseMissing('redirects', ['id' => $redirect->id()]);
    });
});

describe('collection methods', function () {
    it('returns all redirects', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
        $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

        Redirect::save($redirect1);
        Redirect::save($redirect2);
        Redirect::save($redirect3);

        $all = Redirect::all();

        expect($all)->toHaveCount(3);
    });

    it('returns only enabled redirects', function () {
        $enabled1 = Redirect::make()->source('/enabled-1')->destination('/new-1')->enabled(true);
        $enabled2 = Redirect::make()->source('/enabled-2')->destination('/new-2')->enabled(true);
        $disabled = Redirect::make()->source('/disabled')->destination('/new-3')->enabled(false);

        Redirect::save($enabled1);
        Redirect::save($enabled2);
        Redirect::save($disabled);

        $enabled = Redirect::enabled();

        expect($enabled)->toHaveCount(2);
    });

    it('returns redirects ordered by order column', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
        $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

        Redirect::save($redirect1);
        Redirect::save($redirect2);
        Redirect::save($redirect3);

        Redirect::reorder([$redirect3->id(), $redirect1->id(), $redirect2->id()]);

        $ordered = Redirect::ordered();

        expect($ordered->first()->id())->toBe($redirect3->id());
        expect($ordered->get(1)->id())->toBe($redirect1->id());
        expect($ordered->last()->id())->toBe($redirect2->id());
    });

    it('returns ordered enabled redirects', function () {
        $enabled1 = Redirect::make()->source('/enabled-1')->destination('/new-1')->enabled(true);
        $enabled2 = Redirect::make()->source('/enabled-2')->destination('/new-2')->enabled(true);
        $disabled = Redirect::make()->source('/disabled')->destination('/new-3')->enabled(false);

        Redirect::save($enabled1);
        Redirect::save($enabled2);
        Redirect::save($disabled);

        Redirect::reorder([$enabled2->id(), $disabled->id(), $enabled1->id()]);

        Blink::flush();

        $orderedEnabled = Redirect::orderedEnabled();

        expect($orderedEnabled)->toHaveCount(2);
        expect($orderedEnabled->first()->id())->toBe($enabled2->id());
        expect($orderedEnabled->last()->id())->toBe($enabled1->id());
    });
});

describe('database-specific operations', function () {
    it('saves redirect to database', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');

        Redirect::save($redirect);

        $this->assertDatabaseHas('redirects', [
            'id'          => $redirect->id(),
            'source'      => '/old-page',
            'destination' => '/new-page',
        ]);
    });

    it('deletes redirect from database', function () {
        $redirect = Redirect::make()
            ->source('/old-page')
            ->destination('/new-page');
        Redirect::save($redirect);

        $this->assertDatabaseHas('redirects', ['id' => $redirect->id()]);

        Redirect::delete($redirect);

        $this->assertDatabaseMissing('redirects', ['id' => $redirect->id()]);
    });
});

describe('auto-order assignment', function () {
    it('assigns next order value on save for new redirect', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
        $redirect3 = Redirect::make()->source('/page-3')->destination('/new-3');

        Redirect::save($redirect1);
        Redirect::save($redirect2);
        Redirect::save($redirect3);

        $this->assertDatabaseHas('redirects', ['id' => $redirect1->id(), 'order' => 0]);
        $this->assertDatabaseHas('redirects', ['id' => $redirect2->id(), 'order' => 1]);
        $this->assertDatabaseHas('redirects', ['id' => $redirect3->id(), 'order' => 2]);
    });

    it('preserves order on update', function () {
        $redirect = Redirect::make()->source('/page-1')->destination('/new-1');
        Redirect::save($redirect);

        $originalOrder = $redirect->order();

        $redirect->destination('/updated-page');
        Redirect::save($redirect);

        $this->assertDatabaseHas('redirects', [
            'id'    => $redirect->id(),
            'order' => $originalOrder,
        ]);
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
