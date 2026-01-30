<?php

use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Import\ColumnMapper;
use Ndx\SimpleRedirect\Import\CsvImporter;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;
use Statamic\Facades\Site;
use Statamic\Sites\Site as SiteInstance;

uses(WithFileDriver::class);

function createCsv(array $rows): string
{
    return implode("\n", array_map(
        fn ($row) => implode(',', array_map(fn ($value) => '"' . $value . '"', $row)),
        $rows
    ));
}

describe('import', function () {
    it('imports valid redirects', function () {
        $csv = createCsv([
            ['source', 'destination', 'status_code'],
            ['/old', '/new', '301'],
            ['/foo', '/bar', '302'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);
    });

    it('applies default values for missing columns', function () {
        $csv = createCsv([
            ['source', 'destination'],
            ['/old', '/new'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirect = Redirect::all()->first();

        expect($redirect->statusCode())->toBe(301);
        expect($redirect->isEnabled())->toBeTrue();
        expect($redirect->isRegex())->toBeFalse();
    });

    it('skips duplicates when source and destination match', function () {
        Redirect::save(Redirect::make()->source('/existing')->destination('/test'));

        $csv = createCsv([
            ['source', 'destination'],
            ['/existing', '/test'],
            ['/new', '/destination'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);
    });

    it('imports when source matches but destination differs', function () {
        Redirect::save(Redirect::make()->source('/existing')->destination('/original'));

        $csv = createCsv([
            ['source', 'destination'],
            ['/existing', '/changed'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);

        $sources = Redirect::all()->map(fn ($redirect) => $redirect->source())->toArray();

        expect($sources)->toContain('/existing');
    });

    it('skips rows with missing required fields', function () {
        $csv = createCsv([
            ['source', 'destination'],
            ['', '/new'],
            ['/old', ''],
            ['/valid', '/destination'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(1);
        expect(Redirect::all()->first()->source())->toBe('/valid');
    });
});

describe('getMapping', function () {
    it('returns column mapping', function () {
        $csv = createCsv([
            ['source', 'destination', 'type', 'enabled'],
            ['/old', '/new', '301', '1'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $mapping  = $importer->getMapping($csv);

        expect($mapping)->toMatchArray([
            'source'      => 'source',
            'destination' => 'destination',
            'type'        => 'status_code',
            'enabled'     => 'enabled',
        ]);
    });
});

describe('external format support', function () {
    it('imports format with type and match_type columns', function () {
        $csv = createCsv([
            ['enabled', 'source', 'source_md5', 'destination', 'type', 'site', 'match_type', 'description', 'order'],
            ['1', '/foo', 'abc123', '/bar', '410', 'default', 'exact', '', '1'],
            ['0', '/test', 'def456', '/result', '301', 'default', 'regex', 'A description', '0'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);

        $redirects = Redirect::all();
        $first     = $redirects->first(fn ($r) => $r->source() === '/foo');
        $second    = $redirects->first(fn ($r) => $r->source() === '/test');

        expect($first->statusCode())->toBe(410);
        expect($first->isEnabled())->toBeTrue();
        expect($first->isRegex())->toBeFalse();

        expect($second->statusCode())->toBe(301);
        expect($second->isEnabled())->toBeFalse();
        expect($second->isRegex())->toBeTrue();
    });

    it('imports alt-redirect format', function () {
        $csv = createCsv([
            ['from', 'to', 'redirect_type', 'sites', 'id'],
            ['/old-page', '/new-page', '301', 'default', 'abc-123'],
            ['/another', '/destination', '302', '', 'def-456'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);

        $redirects = Redirect::all();
        $first     = $redirects->first(fn ($r) => $r->source() === '/old-page');
        $second    = $redirects->first(fn ($r) => $r->source() === '/another');

        expect($first->destination())->toBe('/new-page');
        expect($first->statusCode())->toBe(301);

        expect($second->destination())->toBe('/destination');
        expect($second->statusCode())->toBe(302);
    });

    it('handles match_type conversion correctly', function () {
        $csv = createCsv([
            ['source', 'destination', 'match_type'],
            ['/exact', '/test1', 'exact'],
            ['/regex', '/test2', 'regex'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirects = Redirect::all();
        $exact     = $redirects->first(fn ($r) => $r->source() === '/exact');
        $regex     = $redirects->first(fn ($r) => $r->source() === '/regex');

        expect($exact->isRegex())->toBeFalse();
        expect($regex->isRegex())->toBeTrue();
    });
});

describe('order column', function () {
    it('imports redirects sorted by order column', function () {
        $csv = createCsv([
            ['source', 'destination', 'order'],
            ['/third', '/c', '2'],
            ['/first', '/a', '0'],
            ['/second', '/b', '1'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $ordered = Redirect::ordered()->values();

        expect($ordered->get(0)->source())->toBe('/first');
        expect($ordered->get(1)->source())->toBe('/second');
        expect($ordered->get(2)->source())->toBe('/third');
    });

    it('imports redirects in csv order when no order column', function () {
        $csv = createCsv([
            ['source', 'destination'],
            ['/first', '/a'],
            ['/second', '/b'],
            ['/third', '/c'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $ordered = Redirect::ordered()->values();

        expect($ordered->get(0)->source())->toBe('/first');
        expect($ordered->get(1)->source())->toBe('/second');
        expect($ordered->get(2)->source())->toBe('/third');
    });

    it('handles empty order values by placing them last', function () {
        $csv = createCsv([
            ['source', 'destination', 'order'],
            ['/no-order', '/x', ''],
            ['/first', '/a', '0'],
            ['/second', '/b', '1'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $ordered = Redirect::ordered()->values();

        expect($ordered->get(0)->source())->toBe('/first');
        expect($ordered->get(1)->source())->toBe('/second');
        expect($ordered->get(2)->source())->toBe('/no-order');
    });
});

describe('sites column', function () {
    it('imports valid sites', function () {
        config()->set('statamic.system.multisite', true);

        Site::setSites(collect([
            'en' => new SiteInstance('en', ['name' => 'English', 'url' => '/', 'locale' => 'en']),
            'de' => new SiteInstance('de', ['name' => 'German', 'url' => '/de/', 'locale' => 'de']),
        ]));

        $csv = createCsv([
            ['source', 'destination', 'site'],
            ['/old', '/new', 'en'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirect = Redirect::all()->first();

        expect($redirect->sites())->toBe(['en']);
    });

    it('imports multiple valid sites', function () {
        config()->set('statamic.system.multisite', true);

        Site::setSites(collect([
            'en' => new SiteInstance('en', ['name' => 'English', 'url' => '/', 'locale' => 'en']),
            'de' => new SiteInstance('de', ['name' => 'German', 'url' => '/de/', 'locale' => 'de']),
            'fr' => new SiteInstance('fr', ['name' => 'French', 'url' => '/fr/', 'locale' => 'fr']),
        ]));

        $csv = createCsv([
            ['source', 'destination', 'sites'],
            ['/old', '/new', 'en,de'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirect = Redirect::all()->first();

        expect($redirect->sites())->toBe(['en', 'de']);
    });

    it('filters out non-existent sites', function () {
        config()->set('statamic.system.multisite', true);

        Site::setSites(collect([
            'en' => new SiteInstance('en', ['name' => 'English', 'url' => '/', 'locale' => 'en']),
            'de' => new SiteInstance('de', ['name' => 'German', 'url' => '/de/', 'locale' => 'de']),
        ]));

        $csv = createCsv([
            ['source', 'destination', 'site'],
            ['/old', '/new', 'default'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirect = Redirect::all()->first();

        expect($redirect->sites())->toBeNull();
    });

    it('keeps valid sites and filters out non-existent ones', function () {
        config()->set('statamic.system.multisite', true);

        Site::setSites(collect([
            'en' => new SiteInstance('en', ['name' => 'English', 'url' => '/', 'locale' => 'en']),
            'de' => new SiteInstance('de', ['name' => 'German', 'url' => '/de/', 'locale' => 'de']),
        ]));

        $csv = createCsv([
            ['source', 'destination', 'sites'],
            ['/old', '/new', 'en,default,nonexistent'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirect = Redirect::all()->first();
        expect($redirect->sites())->toBe(['en']);
    });

    it('sets sites to null when empty', function () {
        $csv = createCsv([
            ['source', 'destination', 'site'],
            ['/old', '/new', ''],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        $redirect = Redirect::all()->first();

        expect($redirect->sites())->toBeNull();
    });
});
