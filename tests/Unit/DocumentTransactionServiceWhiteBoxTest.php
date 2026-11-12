<?php

use App\Services\DocumentTransactionService;

test('white-box: invertOperation produces correct inverse for insert, delete, and update', function () {
    $service = new DocumentTransactionService;

    // 1. Invert insert_block -> delete_block
    $insertOp = [
        'type' => 'insert_block',
        'targetId' => 'blk-new',
        'index' => 2,
        'payload' => ['id' => 'blk-new', 'type' => 'paragraph', 'runs' => []],
    ];
    $invInsert = $service->invertOperation($insertOp);
    expect($invInsert['type'])->toBe('delete_block')
        ->and($invInsert['targetId'])->toBe('blk-new');

    // 2. Invert delete_block -> insert_block
    $deleteOp = [
        'type' => 'delete_block',
        'targetId' => 'blk-old',
        'index' => 1,
        'payload' => ['id' => 'blk-old', 'type' => 'paragraph', 'runs' => []],
    ];
    $invDelete = $service->invertOperation($deleteOp);
    expect($invDelete['type'])->toBe('insert_block')
        ->and($invDelete['payload']['id'])->toBe('blk-old');

    // 3. Invert update_block -> update_block with reversed payloads
    $updateOp = [
        'type' => 'update_block',
        'targetId' => 'blk-target',
        'payload' => ['align' => 'center'],
        'previous' => ['align' => 'left'],
    ];
    $invUpdate = $service->invertOperation($updateOp);
    expect($invUpdate['type'])->toBe('update_block')
        ->and($invUpdate['targetId'])->toBe('blk-target')
        ->and($invUpdate['payload']['align'])->toBe('left')
        ->and($invUpdate['previous']['align'])->toBe('center');
});

test('white-box: applyOperation correctly modifies document model', function () {
    $service = new DocumentTransactionService;

    $initialModel = [
        'schemaVersion' => 1,
        'id' => 'doc-1',
        'metadata' => ['version' => 1],
        'blocks' => [
            ['id' => 'blk-1', 'type' => 'paragraph', 'runs' => [['text' => 'First']]],
            ['id' => 'blk-2', 'type' => 'paragraph', 'runs' => [['text' => 'Second']]],
        ],
    ];

    // Apply insert
    $insertOp = [
        'type' => 'insert_block',
        'index' => 1,
        'payload' => ['id' => 'blk-mid', 'type' => 'paragraph', 'runs' => [['text' => 'Middle']]],
    ];
    $modelAfterInsert = $service->applyOperation($initialModel, $insertOp);
    expect($modelAfterInsert['blocks'])->toHaveCount(3)
        ->and($modelAfterInsert['blocks'][1]['id'])->toBe('blk-mid')
        ->and($modelAfterInsert['metadata']['version'])->toBe(2);

    // Apply delete
    $deleteOp = [
        'type' => 'delete_block',
        'targetId' => 'blk-mid',
    ];
    $modelAfterDelete = $service->applyOperation($modelAfterInsert, $deleteOp);
    expect($modelAfterDelete['blocks'])->toHaveCount(2)
        ->and($modelAfterDelete['blocks'][1]['id'])->toBe('blk-2');
});

test('white-box: searchAndReplace does not damage HTML tags or attributes', function () {
    $service = new DocumentTransactionService;

    $html = '<p class="lead font-bold">Teknologi modern untuk naskah modern.</p><a href="https://modern.com">Buka modern link</a>';
    $result = $service->searchAndReplace($html, 'modern', 'canggih');

    expect($result['success'])->toBeTrue()
        ->and($result['count'])->toBe(3) // 2 inside <p>, 1 inside <a> text
        ->and($result['html'])->toContain('class="lead font-bold"') // attribute untouched
        ->and($result['html'])->toContain('href="https://modern.com"') // url untouched
        ->and($result['html'])->toContain('Teknologi canggih untuk naskah canggih')
        ->and($result['html'])->toContain('Buka canggih link');
});

test('white-box: auditAccessibility flags missing ALT and heading skips', function () {
    $service = new DocumentTransactionService;

    $modelWithIssues = [
        'blocks' => [
            ['id' => 'h1', 'type' => 'heading', 'level' => 1],
            ['id' => 'h3', 'type' => 'heading', 'level' => 3], // skip level 2!
            ['id' => 'fig1', 'type' => 'figure', 'src' => 'pic.png', 'alt' => ''], // missing alt!
            [
                'id' => 'tbl1',
                'type' => 'table',
                'rows' => [
                    ['cells' => [['isHeader' => false, 'runs' => []]]], // missing header th!
                ],
            ],
        ],
    ];

    $audit = $service->auditAccessibility($modelWithIssues);
    expect($audit['passed'])->toBeFalse()
        ->and($audit['issuesCount'])->toBe(3)
        ->and($audit['summary']['imagesMissingAlt'])->toBe(1)
        ->and($audit['summary']['tablesWithoutHeader'])->toBe(1);
});
