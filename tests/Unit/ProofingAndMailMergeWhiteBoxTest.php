<?php

use App\Models\Document;
use App\Services\DocumentProtectionService;
use App\Services\MailMergeService;
use App\Services\ProofingService;
use Tests\TestCase;

uses(TestCase::class);

test('white-box: ProofingService detects PUEBI non-standard words and provides suggestions', function () {
    $service = new ProofingService;

    $html = '<p>Peneliti melakukan analisa terhadap praktek diantara subyek penelitian.</p>';
    $result = $service->checkProofing($html);

    expect($result['success'])->toBeTrue()
        ->and($result['issuesCount'])->toBeGreaterThanOrEqual(3)
        ->and($result['readability'])->toHaveKeys(['readingEaseScore', 'readingLevel', 'wordCount']);

    $suggestions = collect($result['suggestions']);
    expect($suggestions->pluck('suggestion')->toArray())->toContain('analisis')
        ->and($suggestions->pluck('suggestion')->toArray())->toContain('praktik')
        ->and($suggestions->pluck('suggestion')->toArray())->toContain('di antara');
});

test('white-box: segmentForSpeech splits content into sentence chunks with estimated duration', function () {
    $service = new ProofingService;

    $html = '<p>Kalimat pertama pengujian. Kalimat kedua untuk verifikasi sintesis suara! Apakah kalimat ketiga bekerja?</p>';
    $result = $service->segmentForSpeech($html);

    expect($result['success'])->toBeTrue()
        ->and($result['totalSegments'])->toBe(3)
        ->and($result['segments'][0]['text'])->toBe('Kalimat pertama pengujian.')
        ->and($result['segments'][0]['estimatedSeconds'])->toBeGreaterThanOrEqual(1);
});

test('white-box: MailMergeService merges tokens and ensures recipient data isolation (BB12)', function () {
    $service = new MailMergeService;

    $template = 'Kepada Yth. {{nama}}, di {{kota}}. Nomor registrasi: «nomor».';
    $tokens = $service->extractTokens($template);
    expect($tokens)->toContain('nama', 'kota', 'nomor');

    $records = [
        ['nama' => 'Budi Santoso', 'kota' => 'Surabaya', 'nomor' => 'REG-001'],
        ['nama' => 'Siti Rahma', 'kota' => 'Bandung', 'nomor' => 'REG-002'],
    ];

    $batch = $service->mergeBatch($template, $records);
    expect($batch['success'])->toBeTrue()
        ->and($batch['totalRecords'])->toBe(2);

    // Verify recipient 1 has only recipient 1's data
    $res1 = $batch['results'][0]['content'];
    expect($res1)->toContain('Budi Santoso', 'Surabaya', 'REG-001')
        ->and($res1)->not->toContain('Siti Rahma', 'Bandung', 'REG-002');

    // Verify recipient 2 has only recipient 2's data
    $res2 = $batch['results'][1]['content'];
    expect($res2)->toContain('Siti Rahma', 'Bandung', 'REG-002')
        ->and($res2)->not->toContain('Budi Santoso', 'Surabaya', 'REG-001');
});

test('white-box: DocumentProtectionService verifies passwords and restricts modes (BB15)', function () {
    $service = new DocumentProtectionService;

    $doc = Document::create(['title' => 'Dokumen Rahasia']);

    // Set password protection
    $service->setProtection($doc, 'read_only', 'secret1234');
    expect($doc->restricted_mode)->toBe('read_only')
        ->and($service->verifyPassword($doc, 'secret1234'))->toBeTrue()
        ->and($service->verifyPassword($doc, 'wrongpassword'))->toBeFalse();

    // Remove protection
    expect($service->removeProtection($doc, 'wrongpassword'))->toBeFalse();
    expect($service->removeProtection($doc, 'secret1234'))->toBeTrue();
    expect($doc->fresh()->restricted_mode)->toBe('none')
        ->and($doc->fresh()->password_hash)->toBeNull();
});
