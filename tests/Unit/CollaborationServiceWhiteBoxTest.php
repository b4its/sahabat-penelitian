<?php

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Services\CollaborationService;
use Tests\TestCase;

uses(TestCase::class);

test('white-box: createVersionSnapshot creates immutable versions without data loss', function () {
    $service = new CollaborationService;

    $doc = Document::create([
        'title' => 'Riset Bersama',
        'content_html' => '<p>Versi pertama</p>',
        'current_version' => 1,
    ]);

    $v2 = $service->createVersionSnapshot($doc, 'Peneliti 1', 'Revisi bab pendahuluan');
    expect($v2->version_number)->toBe(2)
        ->and($v2->author)->toBe('Peneliti 1')
        ->and($doc->fresh()->current_version)->toBe(2);

    $v3 = $service->createVersionSnapshot($doc, 'Peneliti 2', 'Penambahan metodologi');
    expect($v3->version_number)->toBe(3)
        ->and($doc->fresh()->current_version)->toBe(3);

    expect($doc->versions()->count())->toBe(2);
});

test('white-box: restoreVersion creates a new snapshot restoring old content without destroying history', function () {
    $service = new CollaborationService;

    $doc = Document::create([
        'title' => 'Naskah Pemulihan',
        'content_html' => '<p>Konten V1 Asli</p>',
        'current_version' => 1,
    ]);

    // Snapshot V2
    $service->createVersionSnapshot($doc, 'User A', 'Versi 2 Awal');

    // Mutate document
    $doc->update(['content_html' => '<p>Konten V3 Berubah</p>']);
    $service->createVersionSnapshot($doc, 'User B', 'Versi 3 Modifikasi');

    expect($doc->fresh()->current_version)->toBe(3);

    // Restore version 2
    $restoredVersion = $service->restoreVersion($doc, 2, 'Admin');

    expect($restoredVersion->version_number)->toBe(4)
        ->and($doc->fresh()->content_html)->toBe('<p>Konten V1 Asli</p>')
        ->and($doc->fresh()->current_version)->toBe(4)
        ->and($doc->versions()->count())->toBe(3);
});

test('white-box: checkPermission enforces ACL roles correctly', function () {
    $service = new CollaborationService;

    $doc = Document::create(['title' => 'ACL Test']);

    DocumentPermission::create([
        'document_id' => $doc->id,
        'user_email' => 'editor@univ.ac.id',
        'role' => 'editor',
    ]);

    DocumentPermission::create([
        'document_id' => $doc->id,
        'user_email' => 'viewer@univ.ac.id',
        'role' => 'viewer',
    ]);

    // Editor can view and edit
    expect($service->checkPermission($doc, 'editor@univ.ac.id', 'viewer'))->toBeTrue()
        ->and($service->checkPermission($doc, 'editor@univ.ac.id', 'editor'))->toBeTrue()
        ->and($service->checkPermission($doc, 'editor@univ.ac.id', 'owner'))->toBeFalse();

    // Viewer can only view
    expect($service->checkPermission($doc, 'viewer@univ.ac.id', 'viewer'))->toBeTrue()
        ->and($service->checkPermission($doc, 'viewer@univ.ac.id', 'editor'))->toBeFalse();

    // Unknown user has no access
    expect($service->checkPermission($doc, 'stranger@gmail.com', 'viewer'))->toBeFalse();
});

test('white-box: presence heartbeat and active list management', function () {
    $service = new CollaborationService;
    $uuid = 'doc-presence-test-'.uniqid();

    $active = $service->heartbeatPresence($uuid, 'user-1', 'Dr. Budi', ['line' => 10]);
    expect($active)->toHaveCount(1)
        ->and($active[0]['name'])->toBe('Dr. Budi');

    $active2 = $service->heartbeatPresence($uuid, 'user-2', 'Dr. Ani', ['line' => 20]);
    expect($active2)->toHaveCount(2);

    $fetched = $service->getActivePresence($uuid);
    expect($fetched)->toHaveCount(2);
});
