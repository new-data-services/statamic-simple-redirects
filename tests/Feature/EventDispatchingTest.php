<?php

use Illuminate\Support\Facades\Event;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

describe('RedirectSaved event', function () {
    it('dispatches RedirectSaved event when saving redirect', function () {
        Event::fake([RedirectSaved::class]);

        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new');
        Redirect::save($redirect);

        Event::assertDispatched(RedirectSaved::class);
    });

    it('includes redirect in event payload', function () {
        Event::fake([RedirectSaved::class]);

        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new');
        Redirect::save($redirect);

        Event::assertDispatched(RedirectSaved::class, function ($event) use ($redirect) {
            return $event->redirect->id() === $redirect->id();
        });
    });

    it('dispatches event on update', function () {
        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new');
        Redirect::save($redirect);

        Event::fake([RedirectSaved::class]);

        $redirect->destination('/updated');
        Redirect::save($redirect);

        Event::assertDispatched(RedirectSaved::class);
    });
});

describe('RedirectDeleted event', function () {
    it('dispatches RedirectDeleted event when deleting redirect', function () {
        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new');
        Redirect::save($redirect);

        Event::fake([RedirectDeleted::class]);

        Redirect::delete($redirect);

        Event::assertDispatched(RedirectDeleted::class);
    });

    it('includes redirect in event payload', function () {
        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new');
        Redirect::save($redirect);

        Event::fake([RedirectDeleted::class]);

        Redirect::delete($redirect);

        Event::assertDispatched(RedirectDeleted::class, function ($event) use ($redirect) {
            return $event->redirect->id() === $redirect->id();
        });
    });
});

describe('RedirectTreeSaved event', function () {
    it('dispatches RedirectTreeSaved event when saving tree', function () {
        Event::fake([RedirectTreeSaved::class]);

        $tree = new RedirectTree;
        $tree->handle('test-tree');
        $tree->tree(['id-1', 'id-2']);
        $tree->save();

        Event::assertDispatched(RedirectTreeSaved::class);
    });

    it('includes tree in event payload', function () {
        Event::fake([RedirectTreeSaved::class]);

        $tree = new RedirectTree;
        $tree->handle('test-tree');
        $tree->tree(['id-1', 'id-2']);
        $tree->save();

        Event::assertDispatched(RedirectTreeSaved::class, function ($event) {
            return $event->tree->handle() === 'test-tree' && $event->tree->tree() === ['id-1', 'id-2'];
        });
    });

    it('dispatches event when reordering', function () {
        $redirect1 = Redirect::make()->source('/page-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/page-2')->destination('/new-2');
        Redirect::save($redirect1);
        Redirect::save($redirect2);

        Event::fake([RedirectTreeSaved::class]);

        Redirect::reorder([$redirect2->id(), $redirect1->id()]);

        Event::assertDispatched(RedirectTreeSaved::class);
    });
});
