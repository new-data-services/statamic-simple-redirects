<?php

use Ndx\SimpleRedirect\Actions\DeleteRedirect;
use Ndx\SimpleRedirect\Actions\DisableRedirect;
use Ndx\SimpleRedirect\Actions\EnableRedirect;
use Ndx\SimpleRedirect\Data\Redirect as RedirectData;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

describe('EnableRedirect action', function () {
    it('enables single redirect', function () {
        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new')
            ->enabled(false);
        Redirect::save($redirect);

        $action = new EnableRedirect;
        $action->run(collect([$redirect]), []);

        $found = Redirect::find($redirect->id());
        expect($found->isEnabled())->toBeTrue();
    });

    it('enables multiple redirects in bulk', function () {
        $redirect1 = Redirect::make()->source('/old-1')->destination('/new-1')->enabled(false);
        $redirect2 = Redirect::make()->source('/old-2')->destination('/new-2')->enabled(false);
        Redirect::save($redirect1);
        Redirect::save($redirect2);

        $action = new EnableRedirect;
        $action->run(collect([$redirect1, $redirect2]), []);

        expect(Redirect::find($redirect1->id())->isEnabled())->toBeTrue();
        expect(Redirect::find($redirect2->id())->isEnabled())->toBeTrue();
    });

    it('is only visible for disabled redirects', function () {
        $disabled = (new RedirectData)->enabled(false);
        $enabled  = (new RedirectData)->enabled(true);

        $action = new EnableRedirect;

        expect($action->visibleTo($disabled))->toBeTrue();
        expect($action->visibleTo($enabled))->toBeFalse();
    });

    it('is visible to bulk when at least one is disabled', function () {
        $disabled = (new RedirectData)->enabled(false);
        $enabled  = (new RedirectData)->enabled(true);

        $action = new EnableRedirect;

        expect($action->visibleToBulk(collect([$disabled, $enabled])))->toBeTrue();
        expect($action->visibleToBulk(collect([$enabled])))->toBeFalse();
    });
});

describe('DisableRedirect action', function () {
    it('disables single redirect', function () {
        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new')
            ->enabled(true);
        Redirect::save($redirect);

        $action = new DisableRedirect;
        $action->run(collect([$redirect]), []);

        $found = Redirect::find($redirect->id());
        expect($found->isEnabled())->toBeFalse();
    });

    it('disables multiple redirects in bulk', function () {
        $redirect1 = Redirect::make()->source('/old-1')->destination('/new-1')->enabled(true);
        $redirect2 = Redirect::make()->source('/old-2')->destination('/new-2')->enabled(true);
        Redirect::save($redirect1);
        Redirect::save($redirect2);

        $action = new DisableRedirect;
        $action->run(collect([$redirect1, $redirect2]), []);

        expect(Redirect::find($redirect1->id())->isEnabled())->toBeFalse();
        expect(Redirect::find($redirect2->id())->isEnabled())->toBeFalse();
    });

    it('is only visible for enabled redirects', function () {
        $disabled = (new RedirectData)->enabled(false);
        $enabled  = (new RedirectData)->enabled(true);

        $action = new DisableRedirect;

        expect($action->visibleTo($enabled))->toBeTrue();
        expect($action->visibleTo($disabled))->toBeFalse();
    });

    it('is visible to bulk when at least one is enabled', function () {
        $disabled = (new RedirectData)->enabled(false);
        $enabled  = (new RedirectData)->enabled(true);

        $action = new DisableRedirect;

        expect($action->visibleToBulk(collect([$disabled, $enabled])))->toBeTrue();
        expect($action->visibleToBulk(collect([$disabled])))->toBeFalse();
    });
});

describe('DeleteRedirect action', function () {
    it('deletes single redirect', function () {
        $redirect = Redirect::make()
            ->source('/old')
            ->destination('/new');
        Redirect::save($redirect);

        $action = new DeleteRedirect;
        $action->run(collect([$redirect]), []);

        expect(Redirect::find($redirect->id()))->toBeNull();
    });

    it('deletes multiple redirects in bulk', function () {
        $redirect1 = Redirect::make()->source('/old-1')->destination('/new-1');
        $redirect2 = Redirect::make()->source('/old-2')->destination('/new-2');
        Redirect::save($redirect1);
        Redirect::save($redirect2);

        $action = new DeleteRedirect;
        $action->run(collect([$redirect1, $redirect2]), []);

        expect(Redirect::find($redirect1->id()))->toBeNull();
        expect(Redirect::find($redirect2->id()))->toBeNull();
    });

    it('is visible for all redirects', function () {
        $disabled = (new RedirectData)->enabled(false);
        $enabled  = (new RedirectData)->enabled(true);

        $action = new DeleteRedirect;

        expect($action->visibleTo($disabled))->toBeTrue();
        expect($action->visibleTo($enabled))->toBeTrue();
    });

    it('is marked as dangerous', function () {
        $action = new DeleteRedirect;

        $reflected = new ReflectionClass($action);
        $property  = $reflected->getProperty('dangerous');

        expect($property->getValue($action))->toBeTrue();
    });
});
