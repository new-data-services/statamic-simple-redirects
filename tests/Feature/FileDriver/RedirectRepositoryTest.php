<?php

use Illuminate\Support\Facades\File;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Stache\RedirectsStore;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\Blink;

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

describe('candidate lookup', function () {
    beforeEach(function () {
        $this->wildcard = tap(Redirect::make()->source('/blog/*')->destination('/news'), fn ($redirect) => Redirect::save($redirect));
        $this->exact    = tap(Redirect::make()->source('/Old-Page')->destination('/new-page'), fn ($redirect) => Redirect::save($redirect));
        $this->other    = tap(Redirect::make()->source('/unrelated')->destination('/new-page'), fn ($redirect) => Redirect::save($redirect));
        $this->regex    = tap(Redirect::make()->regex(true)->source('^/news/(\d+)$')->destination('/news'), fn ($redirect) => Redirect::save($redirect));
        $this->disabled = tap(Redirect::make()->source('/old-page')->destination('/new-page')->enabled(false), fn ($redirect) => Redirect::save($redirect));
        $this->absolute = tap(Redirect::make()->source('https://example.com/old-page')->destination('/new-page'), fn ($redirect) => Redirect::save($redirect));
    });

    it('returns pattern redirects and exact matches as candidates in order', function () {
        $ids = Redirect::orderedEnabledCandidates(['/old-page', '/old-page?x=1'])->map->id()->all();

        expect($ids)->toBe([$this->wildcard->id(), $this->exact->id(), $this->regex->id()]);
    });

    it('matches exact sources given as absolute url', function () {
        $ids = Redirect::orderedEnabledCandidates(['https://EXAMPLE.com/old-page'])->map->id()->all();

        expect($ids)->toBe([$this->wildcard->id(), $this->regex->id(), $this->absolute->id()]);
    });
});

describe('candidate lookup index', function () {
    it('updates candidates when a redirect is saved or deleted', function () {
        $redirect = tap(Redirect::make()->source('/old')->destination('/new'), fn ($redirect) => Redirect::save($redirect));

        expect(Redirect::orderedEnabledCandidates(['/other'])->all())->toBe([]);

        Redirect::save($redirect->source('/other/*'));

        expect(Redirect::orderedEnabledCandidates(['/something'])->map->id()->all())->toBe([$redirect->id()]);

        Redirect::delete($redirect);

        expect(Redirect::orderedEnabledCandidates(['/something'])->all())->toBe([]);
    });

    it('picks up redirect files changed on disk', function () {
        config()->set('statamic.stache.watcher', true);

        $simulateNextRequest = function () {
            app('stache')->registerStore((new RedirectsStore)->directory(app('stache')->store('redirects')->directory()));
            Blink::flush();
        };

        $redirect = tap(Redirect::make()->source('/old')->destination('/new'), fn ($redirect) => Redirect::save($redirect));

        $simulateNextRequest();

        expect(Redirect::orderedEnabledCandidates(['/old'])->map->id()->all())->toBe([$redirect->id()]);

        File::put($redirect->path(), "---\nsource: /changed\ndestination: /new\nstatus_code: 301\n---\n");
        touch($redirect->path(), time() + 10);

        $simulateNextRequest();

        expect(Redirect::orderedEnabledCandidates(['/changed'])->map->id()->all())->toBe([$redirect->id()]);
        expect(Redirect::orderedEnabledCandidates(['/old'])->all())->toBe([]);
    });
});

it('keeps redirect files without a source as candidates', function () {
    $redirect = tap(Redirect::make()->source('/old')->destination('/new'), fn ($redirect) => Redirect::save($redirect));

    File::put($redirect->path(), "---\ndestination: /new\nstatus_code: 301\n---\n");
    app('stache')->store('redirects')->clear();

    expect(Redirect::orderedEnabledCandidates(['/old'])->map->id()->all())->toBe([$redirect->id()]);
});
