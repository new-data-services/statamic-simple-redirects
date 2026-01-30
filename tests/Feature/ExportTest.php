<?php

use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Import\CsvExporter;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

describe('CsvExporter integration', function () {
    it('exports all redirects from repository', function () {
        Redirect::save(Redirect::make()->source('/old')->destination('/new'));
        Redirect::save(Redirect::make()->source('/foo')->destination('/bar'));

        $csv = (new CsvExporter)->export();

        expect($csv)->toContain('source,destination,status_code,enabled,regex,sites');
        expect(str_contains($csv, '/old'))->toBeTrue();
        expect(str_contains($csv, '/foo'))->toBeTrue();
    });

    it('maintains order from repository', function () {
        Redirect::save(Redirect::make()->source('/first')->destination('/a'));
        Redirect::save(Redirect::make()->source('/second')->destination('/b'));
        Redirect::save(Redirect::make()->source('/third')->destination('/c'));

        $csv = (new CsvExporter)->export();

        $lines       = explode("\n", trim($csv));
        $firstIndex  = array_search(true, array_map(fn ($l) => str_contains($l, '/first'), $lines));
        $secondIndex = array_search(true, array_map(fn ($l) => str_contains($l, '/second'), $lines));
        $thirdIndex  = array_search(true, array_map(fn ($l) => str_contains($l, '/third'), $lines));

        expect($firstIndex)->toBeLessThan($secondIndex);
        expect($secondIndex)->toBeLessThan($thirdIndex);
    });
});
