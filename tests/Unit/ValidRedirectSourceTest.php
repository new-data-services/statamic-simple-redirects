<?php

use Illuminate\Support\Facades\Validator;
use Ndx\SimpleRedirect\Rules\ValidRedirectSource;

describe('non-regex mode', function () {
    it('passes validation for simple paths', function () {
        $validator = Validator::make(
            ['source' => '/old-page', 'regex' => false],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for paths with query strings', function () {
        $validator = Validator::make(
            ['source' => '/page?foo=bar', 'regex' => false],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for paths with wildcards', function () {
        $validator = Validator::make(
            ['source' => '/blog/*', 'regex' => false],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for any source when regex is false', function () {
        $validator = Validator::make(
            ['source' => 'anything-goes-here', 'regex' => false],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation when regex key is missing from data', function () {
        $validator = Validator::make(
            ['source' => '/page'],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('regex mode - valid patterns', function () {
    it('passes validation for simple regex patterns', function () {
        $validator = Validator::make(
            ['source' => '/blog/[0-9]+', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for regex with capture groups', function () {
        $validator = Validator::make(
            ['source' => '/blog/(.*)/page', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for regex with character classes', function () {
        $validator = Validator::make(
            ['source' => '/users/[a-zA-Z0-9_-]+', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for regex with existing delimiters', function () {
        $validator = Validator::make(
            ['source' => '#^/blog/[0-9]+$#', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for regex with modifiers', function () {
        $validator = Validator::make(
            ['source' => '#/blog/.*#i', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for regex with alternation', function () {
        $validator = Validator::make(
            ['source' => '/blog/(post|article)/[0-9]+', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('regex mode - invalid patterns', function () {
    it('fails validation for invalid regex syntax', function () {
        $validator = Validator::make(
            ['source' => '/blog/[invalid', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for unbalanced parentheses', function () {
        $validator = Validator::make(
            ['source' => '/blog/(unclosed', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for invalid character class', function () {
        $validator = Validator::make(
            ['source' => '/blog/[z-a]', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });
});

describe('dangerous patterns (ReDoS prevention)', function () {
    it('fails validation for nested quantifiers like dot-star-plus', function () {
        $validator = Validator::make(
            ['source' => '/blog/.*+', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for question-plus pattern', function () {
        $validator = Validator::make(
            ['source' => '/blog/?+test', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for star-question pattern', function () {
        $validator = Validator::make(
            ['source' => '/blog/*?test', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('fails validation for double-plus pattern', function () {
        $validator = Validator::make(
            ['source' => '/blog/++', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->fails())->toBeTrue();
    });

    it('passes validation for safe quantifier usage', function () {
        $validator = Validator::make(
            ['source' => '/blog/[a-z]+', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });

    it('passes validation for bounded quantifier', function () {
        $validator = Validator::make(
            ['source' => '/blog/[a-z]{1,10}/end', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });
});

describe('edge cases', function () {
    it('trims whitespace from value', function () {
        $validator = Validator::make(
            ['source' => '  /blog/[0-9]+  ', 'regex' => true],
            ['source' => [new ValidRedirectSource]]
        );

        expect($validator->passes())->toBeTrue();
    });
});
