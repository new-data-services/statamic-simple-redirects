<?php

use Illuminate\Support\Collection;
use Ndx\SimpleRedirect\Contracts\RedirectRepository;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Repositories\EloquentRedirectRepository;
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

it('ignores search but keeps pagination while reordering', function () {
    $response = $this->actingAs($this->user)
        ->getJson(cp_route('simple-redirects.index', ['reordering' => 1, 'search' => 'page-1', 'perPage' => 10, 'page' => 2]))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(10)
        ->and($response->json('data.0.source'))->toBe('/page-11')
        ->and($response->json('meta.total'))->toBe(30)
        ->and($response->json('meta.last_page'))->toBe(3);
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

function bindRepositoryWithoutFullLoad(): void
{
    app()->instance(RedirectRepository::class, new class extends EloquentRedirectRepository
    {
        public function all(): Collection
        {
            throw new RuntimeException('all() must not be called');
        }

        public function ordered(): Collection
        {
            throw new RuntimeException('ordered() must not be called');
        }
    });

    Redirect::clearResolvedInstances();
}

it('lists redirects without loading all of them', function () {
    bindRepositoryWithoutFullLoad();

    $response = $this->actingAs($this->user)
        ->getJson(cp_route('simple-redirects.index', ['perPage' => 10, 'page' => 2, 'search' => 'page-1']))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.source'))->toBe('/page-19')
        ->and($response->json('meta.total'))->toBe(11);
});

it('counts redirects without loading all of them', function () {
    bindRepositoryWithoutFullLoad();

    $response = $this->actingAs($this->user)
        ->withHeader('X-Inertia', 'true')
        ->get(cp_route('simple-redirects.index'))
        ->assertOk();

    expect($response->json('props.redirectsCount'))->toBe(30);
});
