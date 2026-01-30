<?php

use Ndx\SimpleRedirect\Facades\Redirect;
use Ndx\SimpleRedirect\Import\CsvExporter;
use Ndx\SimpleRedirect\Tests\Concerns\WithFileDriver;

uses(WithFileDriver::class);

describe('export', function () {
    it('exports empty csv with headers when no redirects', function () {
        $csv   = (new CsvExporter)->export();
        $lines = explode("\n", trim($csv));

        expect($csv)->toContain('source,destination,status_code,enabled,regex,sites');
        expect($lines)->toHaveCount(1);
    });

    it('exports redirects in correct format', function () {
        Redirect::save(
            Redirect::make()
                ->source('/old')
                ->destination('/new')
                ->statusCode(301)
                ->enabled(true)
                ->regex(false)
        );

        $csv = (new CsvExporter)->export();

        expect($csv)->toContain('source,destination,status_code,enabled,regex,sites');
        expect($csv)->toContain('/old,/new,301,1,0,');
    });

    it('exports multiple redirects in order', function () {
        Redirect::save(Redirect::make()->source('/first')->destination('/a'));
        Redirect::save(Redirect::make()->source('/second')->destination('/b'));
        Redirect::save(Redirect::make()->source('/third')->destination('/c'));

        $csv   = (new CsvExporter)->export();
        $lines = explode("\n", trim($csv));

        expect($lines)->toHaveCount(4);
        expect($lines[1])->toContain('/first');
        expect($lines[2])->toContain('/second');
        expect($lines[3])->toContain('/third');
    });

    it('exports sites column', function () {
        Redirect::save(
            Redirect::make()
                ->source('/multi')
                ->destination('/site')
        );

        $csv = (new CsvExporter)->export();

        expect($csv)->toContain('source,destination,status_code,enabled,regex,sites');
        expect($csv)->toContain('/multi,/site');
    });

    it('exports boolean values correctly', function () {
        Redirect::save(
            Redirect::make()
                ->source('/enabled')
                ->destination('/test')
                ->enabled(true)
                ->regex(true)
        );

        Redirect::save(
            Redirect::make()
                ->source('/disabled')
                ->destination('/test2')
                ->enabled(false)
                ->regex(false)
        );

        $csv = (new CsvExporter)->export();

        expect($csv)->toContain('/enabled,/test,301,1,1,');
        expect($csv)->toContain('/disabled,/test2,301,0,0,');
    });
});

describe('filename', function () {
    it('generates filename with current date', function () {
        $filename = (new CsvExporter)->filename();

        expect($filename)->toMatch('/^redirects-\d{4}-\d{2}-\d{2}\.csv$/');
        expect($filename)->toContain(date('Y-m-d'));
    });
});
