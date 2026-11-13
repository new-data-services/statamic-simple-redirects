<?php

use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Tests\Concerns\WithEloquentDriver;
use Statamic\Facades\Site;
use Statamic\Facades\User;
use Statamic\Sites\Site as SiteInstance;

uses(WithEloquentDriver::class);

beforeEach(function () {
    $this->user = tap(User::make()->email('test@example.com')->makeSuper())->save();

    foreach (range(1, 30) as $number) {
        Redirect::save(Redirect::make()->source("/page-{$number}")->destination("/new-{$number}"));
    }
});

it('returns the first page in tree order', function () {
    $response = $this->actingAs($this->user)
        ->getJson(cp_route('simple-redirects.index', ['perPage' => 10]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('data.0.source'))->toBe('/page-1')
        ->and($response->json('meta.total'))->toBe(30)
        ->and($response->json('meta.last_page'))->toBe(3)
        ->and($response->json('meta.current_page'))->toBe(1);
});

it('ignores search and pagination while reordering', function () {
    $response = $this->actingAs($this->user)
        ->getJson(cp_route('simple-redirects.index', ['reordering' => 1, 'search' => 'page-1', 'perPage' => 10, 'page' => 2]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(30)
        ->and($response->json('meta.last_page'))->toBe(1);
});

it('filters by site', function () {
    config()->set('statamic.editions.pro', true);
    config()->set('statamic.system.multisite', true);

    Site::setSites(collect([
        'en' => new SiteInstance('en', ['name' => 'English', 'url' => '/', 'locale' => 'en']),
        'de' => new SiteInstance('de', ['name' => 'German', 'url' => '/de/', 'locale' => 'de']),
    ]));

    Redirect::save(Redirect::make()->source('/en-only')->destination('/new')->sites(['en']));
    Redirect::save(Redirect::make()->source('/de-only')->destination('/new')->sites(['de']));
    Redirect::save(Redirect::make()->source('/none-selected')->destination('/new')->sites([]));

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('simple-redirects.index', [
            'perPage' => 50,
            'filters' => base64_encode(json_encode(['redirect_site' => ['sites' => ['de']]])),
        ]))
        ->assertOk();

    expect($response->json('meta.total'))->toBe(32)
        ->and(collect($response->json('data'))->pluck('source'))->not->toContain('/en-only')
        ->toContain('/de-only', '/none-selected', '/page-1');
});
