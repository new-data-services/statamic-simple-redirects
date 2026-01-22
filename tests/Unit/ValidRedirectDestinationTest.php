<?php

use Illuminate\Support\Facades\Validator;
use Ndx\SimpleRedirect\Rules\ValidRedirectDestination;

describe('valid destinations', function () {
    it('passes validation for relative paths', function () {
        $validator = Validator::make(
            ['destination' => '/new-page'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for paths without leading slash', function () {
        $validator = Validator::make(
            ['destination' => 'new-page'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for http urls', function () {
        $validator = Validator::make(
            ['destination' => 'http://example.com/page'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for https urls', function () {
        $validator = Validator::make(
            ['destination' => 'https://example.com/page'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for paths with query parameters', function () {
        $validator = Validator::make(
            ['destination' => '/page?foo=bar&baz=qux'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for paths with fragments', function () {
        $validator = Validator::make(
            ['destination' => '/page#section'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for paths with capture placeholders', function () {
        $validator = Validator::make(
            ['destination' => '/articles/$1'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for external urls with capture placeholders', function () {
        $validator = Validator::make(
            ['destination' => 'https://example.com/$1/$2'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for mailto links', function () {
        $validator = Validator::make(
            ['destination' => 'mailto:test@example.com'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for tel links', function () {
        $validator = Validator::make(
            ['destination' => 'tel:+1234567890'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('blocked protocols', function () {
    it('fails validation for javascript protocol', function () {
        $validator = Validator::make(
            ['destination' => 'javascript:alert(1)'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for data protocol', function () {
        $validator = Validator::make(
            ['destination' => 'data:text/html,<script>alert(1)</script>'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for vbscript protocol', function () {
        $validator = Validator::make(
            ['destination' => 'vbscript:msgbox("test")'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for file protocol', function () {
        $validator = Validator::make(
            ['destination' => 'file:///etc/passwd'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('protocol check is case insensitive for javascript', function () {
        $validator = Validator::make(
            ['destination' => 'JAVASCRIPT:alert(1)'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('protocol check is case insensitive for data', function () {
        $validator = Validator::make(
            ['destination' => 'DATA:text/html,test'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('only blocks protocols at the start of string', function () {
        $validator = Validator::make(
            ['destination' => '/page?url=javascript:test'],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('edge cases', function () {
    it('trims whitespace from value', function () {
        $validator = Validator::make(
            ['destination' => '  /new-page  '],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('fails for javascript with leading whitespace after trim', function () {
        $validator = Validator::make(
            ['destination' => '  javascript:alert(1)  '],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('passes validation for empty string', function () {
        $validator = Validator::make(
            ['destination' => ''],
            ['destination' => [new ValidRedirectDestination]]
        );

        expect($validator->passes())->toBeTrue();
    });
});
