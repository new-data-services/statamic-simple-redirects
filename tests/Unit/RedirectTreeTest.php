<?php

use Ndx\SimpleRedirect\Data\RedirectTree;

describe('fluent getters and setters', function () {
    it('has default handle of redirects', function () {
        $tree = new RedirectTree;

        expect($tree->handle())->toBe('redirects');
    });

    it('can get and set handle', function () {
        $tree = new RedirectTree;
        $tree->handle('custom-handle');

        expect($tree->handle())->toBe('custom-handle');
    });

    it('has empty tree by default', function () {
        $tree = new RedirectTree;

        expect($tree->tree())->toBe([]);
    });

    it('can get and set tree array', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3']);

        expect($tree->tree())->toBe(['id-1', 'id-2', 'id-3']);
    });
});

describe('tree operations', function () {
    it('appends id to tree', function () {
        $tree = new RedirectTree;

        $tree->append('id-1');
        $tree->append('id-2');

        expect($tree->tree())->toBe(['id-1', 'id-2']);
    });

    it('does not duplicate id when appending', function () {
        $tree = new RedirectTree;

        $tree->append('id-1');
        $tree->append('id-1');

        expect($tree->tree())->toBe(['id-1']);
    });

    it('removes id from tree', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3']);

        $tree->remove('id-2');

        expect($tree->tree())->toBe(['id-1', 'id-3']);
    });

    it('reindexes tree after removal', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3']);

        $tree->remove('id-1');

        expect($tree->tree())->toBe(['id-2', 'id-3']);
        expect(array_keys($tree->tree()))->toBe([0, 1]);
    });

    it('handles removal of non-existent id gracefully', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2']);

        $tree->remove('id-999');

        expect($tree->tree())->toBe(['id-1', 'id-2']);
    });

    it('moves id to new position at start', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3']);

        $tree->move('id-3', 0);

        expect($tree->tree())->toBe(['id-3', 'id-1', 'id-2']);
    });

    it('moves id to new position in middle', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3', 'id-4']);

        $tree->move('id-4', 1);

        expect($tree->tree())->toBe(['id-1', 'id-4', 'id-2', 'id-3']);
    });

    it('moves id to end position', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3']);

        $tree->move('id-1', 2);

        expect($tree->tree())->toBe(['id-2', 'id-3', 'id-1']);
    });

    it('returns self for fluent chaining', function () {
        $tree = new RedirectTree;

        $result = $tree->append('id-1');
        expect($result)->toBe($tree);

        $result = $tree->remove('id-1');
        expect($result)->toBe($tree);

        $tree->append('id-1')->append('id-2');
        $result = $tree->move('id-1', 1);
        expect($result)->toBe($tree);
    });
});

describe('serialization', function () {
    it('generates correct file data', function () {
        $tree = new RedirectTree;
        $tree->tree(['id-1', 'id-2', 'id-3']);

        expect($tree->fileData())->toBe([
            'tree' => ['id-1', 'id-2', 'id-3'],
        ]);
    });

    it('generates empty tree in file data', function () {
        $tree = new RedirectTree;

        expect($tree->fileData())->toBe([
            'tree' => [],
        ]);
    });
});
