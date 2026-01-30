<?php

use Ndx\SimpleRedirect\Import\ColumnMapper;

describe('column mapping', function () {
    it('maps standard column names', function () {
        $mapper  = new ColumnMapper;
        $headers = ['source', 'destination', 'status_code', 'enabled', 'regex', 'sites'];

        $mapping = $mapper->map($headers);

        expect($mapping)->toMatchArray([
            'source'      => 'source',
            'destination' => 'destination',
            'status_code' => 'status_code',
            'enabled'     => 'enabled',
            'regex'       => 'regex',
            'sites'       => 'sites',
        ]);
    });

    it('maps common aliases', function () {
        $mapper  = new ColumnMapper;
        $headers = ['from', 'to', 'code', 'active'];

        $mapping = $mapper->map($headers);

        expect($mapping)->toMatchArray([
            'from'   => 'source',
            'to'     => 'destination',
            'code'   => 'status_code',
            'active' => 'enabled',
        ]);
    });

    it('maps external format with type and match_type columns', function () {
        $mapper  = new ColumnMapper;
        $headers = ['enabled', 'source', 'source_md5', 'destination', 'type', 'site', 'match_type', 'description', 'order'];

        $mapping = $mapper->map($headers);

        expect($mapping)->toMatchArray([
            'enabled'     => 'enabled',
            'source'      => 'source',
            'destination' => 'destination',
            'type'        => 'status_code',
            'site'        => 'sites',
            'match_type'  => 'match_type',
            'order'       => 'order',
        ]);
        expect($mapping)->not->toHaveKey('source_md5');
        expect($mapping)->not->toHaveKey('description');
    });

    it('maps alt-redirect format', function () {
        $mapper  = new ColumnMapper;
        $headers = ['from', 'to', 'redirect_type', 'sites', 'id'];

        $mapping = $mapper->map($headers);

        expect($mapping)->toMatchArray([
            'from'          => 'source',
            'to'            => 'destination',
            'redirect_type' => 'status_code',
            'sites'         => 'sites',
        ]);
        expect($mapping)->not->toHaveKey('id');
    });

    it('handles case-insensitive matching', function () {
        $mapper  = new ColumnMapper;
        $headers = ['SOURCE', 'Destination', 'Status_Code'];

        $mapping = $mapper->map($headers);

        expect($mapping)->toMatchArray([
            'SOURCE'      => 'source',
            'Destination' => 'destination',
            'Status_Code' => 'status_code',
        ]);
    });

    it('ignores unknown columns', function () {
        $mapper  = new ColumnMapper;
        $headers = ['source', 'destination', 'unknown_column', 'another_unknown'];

        $mapping = $mapper->map($headers);

        expect($mapping)->toHaveCount(2);
        expect($mapping)->not->toHaveKey('unknown_column');
        expect($mapping)->not->toHaveKey('another_unknown');
    });
});

describe('required columns validation', function () {
    it('returns true when required columns present', function () {
        $mapper  = new ColumnMapper;
        $mapping = ['source' => 'source', 'destination' => 'destination'];

        expect($mapper->hasRequiredColumns($mapping))->toBeTrue();
    });

    it('returns true when required columns mapped from aliases', function () {
        $mapper  = new ColumnMapper;
        $mapping = ['from' => 'source', 'to' => 'destination'];

        expect($mapper->hasRequiredColumns($mapping))->toBeTrue();
    });

    it('returns false when source missing', function () {
        $mapper  = new ColumnMapper;
        $mapping = ['destination' => 'destination'];

        expect($mapper->hasRequiredColumns($mapping))->toBeFalse();
    });

    it('returns false when destination missing', function () {
        $mapper  = new ColumnMapper;
        $mapping = ['source' => 'source'];

        expect($mapper->hasRequiredColumns($mapping))->toBeFalse();
    });

    it('returns false with empty mapping', function () {
        $mapper  = new ColumnMapper;
        $mapping = [];

        expect($mapper->hasRequiredColumns($mapping))->toBeFalse();
    });
});
