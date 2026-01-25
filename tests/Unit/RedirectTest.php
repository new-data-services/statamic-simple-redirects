<?php

use Ndx\SimpleRedirect\Data\Redirect;
use Ndx\SimpleRedirect\Models\Redirect as RedirectModel;

describe('fluent getters and setters', function () {
    it('generates uuid on construction', function () {
        $redirect = new Redirect;

        expect($redirect->id())->not->toBeNull();
        expect($redirect->id())->toMatch('/^[a-f0-9-]{36}$/');
    });

    it('can get and set id', function () {
        $redirect = new Redirect;
        $redirect->id('custom-id');

        expect($redirect->id())->toBe('custom-id');
    });

    it('can get and set source', function () {
        $redirect = new Redirect;
        $redirect->source('/old-page');

        expect($redirect->source())->toBe('/old-page');
    });

    it('can get and set destination', function () {
        $redirect = new Redirect;
        $redirect->destination('/new-page');

        expect($redirect->destination())->toBe('/new-page');
    });

    it('can get and set regex flag', function () {
        $redirect = new Redirect;
        $redirect->regex(true);

        expect($redirect->regex())->toBeTrue();
        expect($redirect->isRegex())->toBeTrue();
    });

    it('can get and set status code', function () {
        $redirect = new Redirect;
        $redirect->statusCode(302);

        expect($redirect->statusCode())->toBe(302);
    });

    it('can get and set enabled flag', function () {
        $redirect = new Redirect;
        $redirect->enabled(false);

        expect($redirect->enabled())->toBeFalse();
        expect($redirect->isEnabled())->toBeFalse();
    });

    it('can get and set order', function () {
        $redirect = new Redirect;
        $redirect->order(5);

        expect($redirect->order())->toBe(5);
    });

    it('has sensible defaults', function () {
        $redirect = new Redirect;

        expect($redirect->regex())->toBeFalse();
        expect($redirect->statusCode())->toBe(301);
        expect($redirect->enabled())->toBeTrue();
        expect($redirect->order())->toBeNull();
    });
});

describe('source normalization', function () {
    it('prepends slash to source for non-regex patterns', function () {
        $redirect = new Redirect;
        $redirect->source('old-page');

        expect($redirect->source())->toBe('/old-page');
    });

    it('does not double slash when source starts with slash', function () {
        $redirect = new Redirect;
        $redirect->source('/old-page');

        expect($redirect->source())->toBe('/old-page');
    });

    it('does not modify source for regex patterns', function () {
        $redirect = new Redirect;
        $redirect->regex(true);
        $redirect->source('old-.*');

        expect($redirect->source())->toBe('old-.*');
    });

    it('re-normalizes source when regex flag changes from true to false', function () {
        $redirect = new Redirect;
        $redirect->regex(true);
        $redirect->source('old-page');
        $redirect->regex(false);

        expect($redirect->source())->toBe('/old-page');
    });
});

describe('wildcard pattern matching', function () {
    it('matches exact path', function () {
        $redirect = (new Redirect)->source('/old-page');

        expect($redirect->matches('/old-page'))->toBeTrue();
    });

    it('does not match different path', function () {
        $redirect = (new Redirect)->source('/old-page');

        expect($redirect->matches('/other-page'))->toBeFalse();
    });

    it('matches path with single wildcard', function () {
        $redirect = (new Redirect)->source('/blog/*');

        expect($redirect->matches('/blog/my-post'))->toBeTrue();
        expect($redirect->matches('/blog/another-post'))->toBeTrue();
    });

    it('does not match wildcard when prefix differs', function () {
        $redirect = (new Redirect)->source('/blog/*');

        expect($redirect->matches('/news/my-post'))->toBeFalse();
    });

    it('matches path with multiple wildcards', function () {
        $redirect = (new Redirect)->source('/*/posts/*');

        expect($redirect->matches('/en/posts/123'))->toBeTrue();
        expect($redirect->matches('/de/posts/hello'))->toBeTrue();
    });

    it('matching is case insensitive', function () {
        $redirect = (new Redirect)->source('/old-page');

        expect($redirect->matches('/OLD-PAGE'))->toBeTrue();
        expect($redirect->matches('/Old-Page'))->toBeTrue();
    });
});

describe('regex pattern matching', function () {
    it('matches valid regex pattern', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('/blog/[0-9]+');

        expect($redirect->matches('/blog/123'))->toBeTrue();
        expect($redirect->matches('/blog/456'))->toBeTrue();
    });

    it('does not match when regex fails', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('/blog/[0-9]+');

        expect($redirect->matches('/blog/abc'))->toBeFalse();
    });

    it('matches regex with capture groups', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('/old/(.*)/page');

        expect($redirect->matches('/old/something/page'))->toBeTrue();
    });

    it('handles regex with custom delimiters', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('#^/blog/[0-9]+$#');

        expect($redirect->matches('/blog/123'))->toBeTrue();
    });

    it('normalizes regex without delimiters', function () {
        $pattern = Redirect::normalizeRegexPattern('/blog/.*');

        expect($pattern)->toBe('#^/blog/.*$#');
    });

    it('preserves regex with existing delimiters', function () {
        $pattern = Redirect::normalizeRegexPattern('#/blog/.*#i');

        expect($pattern)->toBe('#/blog/.*#i');
    });
});

describe('destination building', function () {
    it('returns destination unchanged for simple patterns', function () {
        $redirect = (new Redirect)
            ->source('/old-page')
            ->destination('/new-page');

        expect($redirect->buildDestination('/old-page'))->toBe('/new-page');
    });

    it('replaces single capture group placeholder', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('/blog/(.*)')
            ->destination('/articles/$1');

        expect($redirect->buildDestination('/blog/my-post'))->toBe('/articles/my-post');
    });

    it('replaces multiple capture group placeholders', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('/([a-z]+)/posts/([0-9]+)')
            ->destination('/articles/$2?lang=$1');

        expect($redirect->buildDestination('/en/posts/123'))->toBe('/articles/123?lang=en');
    });

    it('preserves placeholder if capture group missing', function () {
        $redirect = (new Redirect)
            ->regex(true)
            ->source('/blog/(.*)')
            ->destination('/articles/$1/$2');

        expect($redirect->buildDestination('/blog/post'))->toBe('/articles/post/$2');
    });

    it('replaces wildcard captures in destination', function () {
        $redirect = (new Redirect)
            ->source('/blog/*')
            ->destination('/articles/$1');

        expect($redirect->buildDestination('/blog/my-post'))->toBe('/articles/my-post');
    });
});

describe('serialization', function () {
    it('converts to array with all properties', function () {
        $redirect = (new Redirect)
            ->id('test-id')
            ->source('/old')
            ->destination('/new')
            ->regex(true)
            ->statusCode(302)
            ->enabled(false);

        expect($redirect->toArray())->toBe([
            'id'          => 'test-id',
            'source'      => '/old',
            'destination' => '/new',
            'regex'       => true,
            'status_code' => 302,
            'enabled'     => false,
            'sites'       => null,
        ]);
    });

    it('generates correct file data with defaults', function () {
        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new');

        expect($redirect->fileData())->toBe([
            'source'      => '/old',
            'destination' => '/new',
            'status_code' => 301,
        ]);
    });

    it('includes regex in file data when true', function () {
        $redirect = (new Redirect)
            ->source('old-.*')
            ->destination('/new')
            ->regex(true);

        expect($redirect->fileData())->toHaveKey('regex', true);
    });

    it('includes enabled in file data when false', function () {
        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->enabled(false);

        expect($redirect->fileData())->toHaveKey('enabled', false);
    });

    it('omits regex from file data when false', function () {
        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->regex(false);

        expect($redirect->fileData())->not->toHaveKey('regex');
    });

    it('omits enabled from file data when true', function () {
        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->enabled(true);

        expect($redirect->fileData())->not->toHaveKey('enabled');
    });
});

describe('model conversion', function () {
    it('creates redirect from eloquent model', function () {
        $model = new RedirectModel;
        $model->forceFill([
            'id'          => 'model-id',
            'source'      => '/from-model',
            'destination' => '/to-model',
            'regex'       => true,
            'status_code' => 302,
            'enabled'     => false,
            'order'       => 5,
        ]);

        $redirect = Redirect::fromModel($model);

        expect($redirect->id())->toBe('model-id');
        expect($redirect->source())->toBe('/from-model');
        expect($redirect->destination())->toBe('/to-model');
        expect($redirect->regex())->toBeTrue();
        expect($redirect->statusCode())->toBe(302);
        expect($redirect->enabled())->toBeFalse();
        expect($redirect->order())->toBe(5);
        expect($redirect->model())->toBe($model);
    });

    it('converts redirect to eloquent model', function () {
        $redirect = (new Redirect)
            ->id('redirect-id')
            ->source('/from-redirect')
            ->destination('/to-redirect')
            ->regex(true)
            ->statusCode(302)
            ->enabled(false)
            ->order(3);

        $model = $redirect->toModel();

        expect($model)->toBeInstanceOf(RedirectModel::class);
        expect($model->id)->toBe('redirect-id');
        expect($model->source)->toBe('/from-redirect');
        expect($model->destination)->toBe('/to-redirect');
        expect($model->regex)->toBeTrue();
        expect($model->status_code)->toBe(302);
        expect($model->enabled)->toBeFalse();
        expect($model->order)->toBe(3);
    });

    it('uses existing model when converting', function () {
        $existingModel = new RedirectModel;
        $existingModel->forceFill(['id' => 'existing-id']);

        $redirect = (new Redirect)
            ->model($existingModel)
            ->source('/updated')
            ->destination('/new');

        $model = $redirect->toModel();

        expect($model)->toBe($existingModel);
    });

    it('defaults order to zero when null', function () {
        $redirect = (new Redirect)
            ->source('/test')
            ->destination('/dest');

        $model = $redirect->toModel();

        expect($model->order)->toBe(0);
    });

    it('creates redirect from eloquent model with sites', function () {
        config()->set('statamic.system.multisite', true);

        $model = new RedirectModel;
        $model->forceFill([
            'id'          => 'model-id',
            'source'      => '/from-model',
            'destination' => '/to-model',
            'regex'       => false,
            'status_code' => 301,
            'enabled'     => true,
            'order'       => 0,
            'sites'       => ['en', 'de'],
        ]);

        $redirect = Redirect::fromModel($model);

        expect($redirect->sites())->toBe(['en', 'de']);
    });

    it('converts redirect with sites to eloquent model', function () {
        $redirect = (new Redirect)
            ->id('redirect-id')
            ->source('/test')
            ->destination('/dest')
            ->sites(['en']);

        $model = $redirect->toModel();

        expect($model->sites)->toBe(['en']);
    });
});

describe('sites property', function () {
    it('can get and set sites when multisite enabled', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = new Redirect;
        $redirect->sites(['en', 'de']);

        expect($redirect->sites())->toBe(['en', 'de']);
    });

    it('returns null when sites not set and multisite enabled', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = new Redirect;

        expect($redirect->sites())->toBeNull();
    });

    it('returns null when multisite is disabled even if sites are set', function () {
        config()->set('statamic.system.multisite', false);

        $redirect = (new Redirect)->sites(['en', 'de']);

        expect($redirect->sites())->toBeNull();
    });

    it('returns sites when multisite is enabled', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = (new Redirect)->sites(['en']);

        expect($redirect->sites())->toBe(['en']);
    });
});

describe('appliesToSite', function () {
    it('applies to any site when sites is null', function () {
        $redirect = new Redirect;

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeTrue();
        expect($redirect->appliesToSite('any'))->toBeTrue();
    });

    it('applies to any site when sites is empty array', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = (new Redirect)->sites([]);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeTrue();
    });

    it('applies only to specified sites when multisite enabled', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = (new Redirect)->sites(['en']);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeFalse();
    });

    it('applies to all sites when multisite is disabled', function () {
        config()->set('statamic.system.multisite', false);

        $redirect = (new Redirect)->sites(['en']);

        expect($redirect->appliesToSite('en'))->toBeTrue();
        expect($redirect->appliesToSite('de'))->toBeTrue();
    });
});

describe('sites in file data', function () {
    it('omits sites from file data when null', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->sites(null);

        expect($redirect->fileData())->not->toHaveKey('sites');
    });

    it('omits sites from file data when empty array', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->sites([]);

        expect($redirect->fileData())->not->toHaveKey('sites');
    });

    it('includes sites in file data when restricted and multisite enabled', function () {
        config()->set('statamic.system.multisite', true);

        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->sites(['en', 'de']);

        expect($redirect->fileData())->toHaveKey('sites', ['en', 'de']);
    });

    it('omits sites from file data when multisite is disabled', function () {
        config()->set('statamic.system.multisite', false);

        $redirect = (new Redirect)
            ->source('/old')
            ->destination('/new')
            ->sites(['en']);

        expect($redirect->fileData())->not->toHaveKey('sites');
    });
});

describe('sites in toArray', function () {
    it('includes sites in array representation', function () {
        $redirect = (new Redirect)
            ->id('test-id')
            ->source('/old')
            ->destination('/new')
            ->sites(['en']);

        expect($redirect->toArray())->toHaveKey('sites', ['en']);
    });

    it('includes null sites in array representation', function () {
        $redirect = (new Redirect)
            ->id('test-id')
            ->source('/old')
            ->destination('/new');

        expect($redirect->toArray())->toHaveKey('sites', null);
    });
});
