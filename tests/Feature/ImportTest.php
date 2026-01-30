<?php

use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Import\ColumnMapper;
use Ndx\SimpleRedirect\Import\CsvImporter;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

function makeCsv(array $rows): string
{
    return implode("\n", array_map(
        fn ($row) => implode(',', array_map(fn ($value) => '"' . $value . '"', $row)),
        $rows
    ));
}

describe('CsvImporter integration with repository', function () {
    it('imports redirects and persists to repository', function () {
        $csv = makeCsv([
            ['source', 'destination', 'status_code'],
            ['/old', '/new', '301'],
            ['/foo', '/bar', '302'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);

        $found = Redirect::all()->first(fn ($r) => $r->source() === '/old');
        expect($found)->not->toBeNull();
        expect($found->destination())->toBe('/new');
        expect($found->statusCode())->toBe(301);
    });

    it('skips exact duplicates when source and destination match', function () {
        Redirect::save(Redirect::make()->source('/existing')->destination('/target'));

        $csv = makeCsv([
            ['source', 'destination'],
            ['/existing', '/target'],
            ['/new', '/destination'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);
    });
});

describe('external format integration', function () {
    it('imports format with type and match_type columns', function () {
        $csv = makeCsv([
            ['enabled', 'source', 'source_md5', 'destination', 'type', 'site', 'match_type', 'description', 'order'],
            ['1', '/foo', 'abc123', '/bar', '302', 'default', 'exact', '', '1'],
            ['0', '/regex-test', 'def456', '/result', '301', 'default', 'regex', 'Description', '0'],
        ]);

        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);

        $redirects = Redirect::all();
        $first     = $redirects->first(fn ($r) => $r->source() === '/foo');
        $second    = $redirects->first(fn ($r) => $r->source() === '/regex-test');

        expect($first->statusCode())->toBe(302);
        expect($first->isEnabled())->toBeTrue();
        expect($first->isRegex())->toBeFalse();

        expect($second->statusCode())->toBe(301);
        expect($second->isEnabled())->toBeFalse();
        expect($second->isRegex())->toBeTrue();
    });

    it('imports alt-redirect format', function () {
        $csv = makeCsv([
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
});

describe('round-trip import/export', function () {
    it('can export and re-import redirects', function () {
        Redirect::save(
            Redirect::make()
                ->source('/page-1')
                ->destination('/new-1')
                ->statusCode(301)
                ->enabled(true)
                ->regex(false)
        );
        Redirect::save(
            Redirect::make()
                ->source('/page-2')
                ->destination('/new-2')
                ->statusCode(302)
                ->enabled(false)
                ->regex(true)
        );

        $exporter = new \Ndx\SimpleRedirect\Import\CsvExporter;
        $csv      = $exporter->export();

        // Clear existing redirects
        foreach (Redirect::all() as $redirect) {
            Redirect::delete($redirect);
        }
        expect(Redirect::all())->toHaveCount(0);

        // Re-import
        $importer = new CsvImporter(new ColumnMapper);
        $importer->import($csv);

        expect(Redirect::all())->toHaveCount(2);

        $first = Redirect::all()->first(fn ($r) => $r->source() === '/page-1');
        expect($first->destination())->toBe('/new-1');
        expect($first->statusCode())->toBe(301);
        expect($first->isEnabled())->toBeTrue();
        expect($first->isRegex())->toBeFalse();

        $second = Redirect::all()->first(fn ($r) => $r->source() === '/page-2');
        expect($second->destination())->toBe('/new-2');
        expect($second->statusCode())->toBe(302);
        expect($second->isEnabled())->toBeFalse();
        expect($second->isRegex())->toBeTrue();
    });
});
