<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Data\RedirectTree;
use Ndx\SimpleRedirect\Events\RedirectDeleted;
use Ndx\SimpleRedirect\Events\RedirectMatched;
use Ndx\SimpleRedirect\Events\RedirectSaved;
use Ndx\SimpleRedirect\Events\RedirectTreeSaved;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Http\Middleware\HandleRedirects;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\User;

use function Illuminate\Events\queueable;

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

    it('populates authenticatedUser', function () {
        $user = User::make()->email('test@example.com')->save();
        $this->actingAs($user);

        Event::fake([RedirectSaved::class]);

        $redirect = Redirect::make()->source('/old')->destination('/new');
        Redirect::save($redirect);

        Event::assertDispatched(RedirectSaved::class, function ($event) use ($user) {
            return $event->authenticatedUser?->email() === $user->email();
        });
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

    it('populates authenticatedUser', function () {
        $user = User::make()->email('test@example.com')->save();
        $this->actingAs($user);

        $redirect = Redirect::make()->source('/old')->destination('/new');
        Redirect::save($redirect);

        Event::fake([RedirectDeleted::class]);

        Redirect::delete($redirect);

        Event::assertDispatched(RedirectDeleted::class, function ($event) use ($user) {
            return $event->authenticatedUser?->email() === $user->email();
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

    it('populates authenticatedUser', function () {
        $user = User::make()->email('test@example.com')->save();
        $this->actingAs($user);

        Event::fake([RedirectTreeSaved::class]);

        $tree = new RedirectTree;
        $tree->handle('test-tree');
        $tree->tree(['id-1']);
        $tree->save();

        Event::assertDispatched(RedirectTreeSaved::class, function ($event) use ($user) {
            return $event->authenticatedUser?->email() === $user->email();
        });
    });
});

describe('RedirectMatched event', function () {
    beforeEach(function () {
        Route::middleware(['web', HandleRedirects::class])
            ->any('{any}', fn () => abort(404))
            ->where('any', '.*');
    });

    it('dispatches RedirectMatched event when a redirect matches', function () {
        Event::fake([RedirectMatched::class]);

        $redirect = Redirect::make()->source('/blog/*')->destination('/news/$1');
        Redirect::save($redirect);

        $this->get('/blog/hello?utm_source=newsletter')->assertRedirect('/news/hello?utm_source=newsletter');

        Event::assertDispatched(RedirectMatched::class, function ($event) use ($redirect) {
            return $event->redirect->id() === $redirect->id()
                && $event->matchedUrl     === '/blog/hello'
                && $event->destination    === '/news/hello?utm_source=newsletter';
        });
    });

    it('does not dispatch RedirectMatched event without a match', function () {
        Event::fake([RedirectMatched::class]);

        Redirect::save(Redirect::make()->source('/old')->destination('/new'));

        $this->get('/unknown')->assertNotFound();

        Event::assertNotDispatched(RedirectMatched::class);
    });

    it('does not dispatch RedirectMatched event for disabled redirects', function () {
        Event::fake([RedirectMatched::class]);

        Redirect::save(Redirect::make()->source('/old')->destination('/new')->enabled(false));

        $this->get('/old')->assertNotFound();

        Event::assertNotDispatched(RedirectMatched::class);
    });

    it('populates authenticatedUser', function () {
        $user = User::make()->email('test@example.com')->save();
        $this->actingAs($user);

        Event::fake([RedirectMatched::class]);

        Redirect::save(Redirect::make()->source('/old')->destination('/new'));

        $this->get('/old')->assertRedirect('/new');

        Event::assertDispatched(RedirectMatched::class, function ($event) use ($user) {
            return $event->authenticatedUser?->email() === $user->email();
        });
    });

    it('works with queued listeners', function () {
        $user = User::make()->email('test@example.com')->save();
        $this->actingAs($user);

        Event::listen(queueable(function (RedirectMatched $event) {
            Cache::put('matched-redirect', [$event->redirect->id(), $event->matchedUrl, $event->destination]);
        }));

        $redirect = Redirect::make()->source('/old')->destination('/new');
        Redirect::save($redirect);

        $this->get('/old')->assertRedirect('/new');

        expect(Cache::get('matched-redirect'))->toBe([$redirect->id(), '/old', '/new']);
    });
});
