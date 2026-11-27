<?php

use Ndx\SimpleRedirect\Contracts\Redirect as RedirectContract;
use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Models\Redirect as RedirectModel;
use Ndx\SimpleRedirect\Query\EloquentRedirectQueryBuilder;
use Ndx\SimpleRedirect\Tests\Concerns\WithEloquentDriver;

uses(WithEloquentDriver::class);

function createQueryRedirects(int $count): array
{
    return collect(range(1, $count))->map(function (int $number) {
        $redirect = Redirect::make()->source("/page-{$number}")->destination("/new-{$number}");

        Redirect::save($redirect);

        return $redirect->id();
    })->all();
}

it('returns redirects in order', function () {
    [$first, $second, $third] = createQueryRedirects(3);

    Redirect::reorder([$third, $first, $second]);

    $redirects = Redirect::query()->get();

    expect($redirects->map->id()->all())->toBe([$third, $first, $second])
        ->and($redirects)->each->toBeInstanceOf(RedirectContract::class);
});

it('orders redirects with the same order by id', function () {
    foreach (['c', 'a', 'b'] as $prefix) {
        RedirectModel::create(['id' => "{$prefix}0000000-0000-0000-0000-000000000000", 'source' => "/{$prefix}", 'destination' => '/new', 'order' => 0]);
    }

    $expected = [
        'a0000000-0000-0000-0000-000000000000',
        'b0000000-0000-0000-0000-000000000000',
        'c0000000-0000-0000-0000-000000000000',
    ];

    expect(Redirect::query()->get()->map->id()->all())->toBe($expected)
        ->and(Redirect::ordered()->map->id()->all())->toBe($expected);
});

it('paginates redirects', function () {
    [, , $third, $fourth] = createQueryRedirects(5);

    $paginator = Redirect::query()->paginate(2, ['*'], 'page', 2);

    expect($paginator->getCollection()->map->id()->all())->toBe([$third, $fourth])
        ->and($paginator->total())->toBe(5);
});

it('maps is_enabled to the enabled column', function () {
    createQueryRedirects(2);

    $disabled = tap(Redirect::make()->source('/disabled')->destination('/new')->enabled(false), fn ($redirect) => Redirect::save($redirect));

    expect(Redirect::query()->where('is_enabled', false)->get()->map->id()->all())->toBe([$disabled->id()]);
});

it('filters case-insensitively with like', function () {
    Redirect::save(Redirect::make()->source('/old-page')->destination('/new-page'));

    expect(Redirect::query()->where('source', 'like', '%OLD%')->count())->toBe(1);
});

it('filters by overlapping sites', function () {
    $all     = tap(Redirect::make()->source('/all')->destination('/new'), fn ($redirect) => Redirect::save($redirect));
    $english = tap(Redirect::make()->source('/en')->destination('/new'), fn ($redirect) => Redirect::save($redirect));
    $german  = tap(Redirect::make()->source('/de')->destination('/new'), fn ($redirect) => Redirect::save($redirect));

    RedirectModel::where('id', $english->id())->update(['sites' => json_encode(['en'])]);
    RedirectModel::where('id', $german->id())->update(['sites' => json_encode(['de'])]);

    $ids = Redirect::query()
        ->where(fn ($query) => $query
            ->whereNull('sites')
            ->orWhereJsonLength('sites', 0)
            ->orWhereJsonOverlaps('sites', ['en']))
        ->get()
        ->map->id()
        ->all();

    expect($ids)->toBe([$all->id(), $english->id()]);
});

it('resolves nested queries on the redirect connection', function () {
    config()->set('database.connections.redirects_secondary', ['driver' => 'sqlite', 'database' => ':memory:']);
    config()->set('statamic.redirects.database.connection', 'redirects_secondary');

    expect(app(EloquentRedirectQueryBuilder::class)->getConnection()->getName())->toBe('redirects_secondary');
});

it('compares status codes without lower() so integer columns work on postgres', function () {
    Redirect::save(Redirect::make()->source('/permanent')->destination('/new'));
    Redirect::save(Redirect::make()->source('/temporary')->destination('/new')->statusCode(302));

    $query = Redirect::query()->where('status_code', 'like', '%302%');

    expect(strtolower($query->toSql()))->not->toContain('lower(')
        ->and($query->get()->map->source()->all())->toBe(['/temporary']);
});
