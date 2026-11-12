<?php

use App\Services\DocumentReviewService;

test('white-box: extractRevisions parses both insert and delete revisions', function () {
    $service = new DocumentReviewService;

    $html = '<p>Dokumen ini memiliki <span class="docx-revision" data-revision-id="rev-1" data-revision-type="insert" data-author="Budi" data-date="2026-11-12">penambahan teks</span> dan <span class="docx-revision" data-revision-id="rev-2" data-revision-type="delete" data-author="Siti" data-date="2026-11-12"><del>teks dihapus</del></span>.</p>';

    $revisions = $service->extractRevisions($html);
    expect($revisions)->toHaveCount(2)
        ->and($revisions[0]['id'])->toBe('rev-1')
        ->and($revisions[0]['type'])->toBe('insert')
        ->and($revisions[0]['author'])->toBe('Budi')
        ->and($revisions[1]['id'])->toBe('rev-2')
        ->and($revisions[1]['type'])->toBe('delete')
        ->and($revisions[1]['author'])->toBe('Siti');
});

test('white-box: acceptRevision keeps inserted text and purges deleted text', function () {
    $service = new DocumentReviewService;

    $html = '<p>Mulai <span class="docx-revision" data-revision-id="rev-ins" data-revision-type="insert">baru</span> <span class="docx-revision" data-revision-id="rev-del" data-revision-type="delete"><del>lama</del></span> selesai.</p>';

    // Accept insert: 'baru' is kept, revision span unwrapped
    $afterAcceptIns = $service->acceptRevision($html, 'rev-ins');
    expect($afterAcceptIns)->toContain('Mulai baru')
        ->and($afterAcceptIns)->not->toContain('data-revision-id="rev-ins"');

    // Accept delete: 'lama' is completely purged
    $afterAcceptDel = $service->acceptRevision($afterAcceptIns, 'rev-del');
    expect($afterAcceptDel)->toContain('Mulai baru')
        ->and($afterAcceptDel)->toContain('selesai.')
        ->and($afterAcceptDel)->not->toContain('lama');
});

test('white-box: rejectRevision purges inserted text and restores deleted text', function () {
    $service = new DocumentReviewService;

    $html = '<p>Awal <span class="docx-revision" data-revision-id="rev-ins" data-revision-type="insert">tambahan</span> <span class="docx-revision" data-revision-id="rev-del" data-revision-type="delete"><del>asli</del></span> akhir.</p>';

    // Reject insert: 'tambahan' removed
    $afterRejectIns = $service->rejectRevision($html, 'rev-ins');
    expect($afterRejectIns)->not->toContain('tambahan');

    // Reject delete: 'asli' restored
    $afterRejectDel = $service->rejectRevision($afterRejectIns, 'rev-del');
    expect($afterRejectDel)->toContain('Awal')
        ->and($afterRejectDel)->toContain('asli')
        ->and($afterRejectDel)->toContain('akhir.');
});

test('white-box: compareDocuments generates semantic word-level diff', function () {
    $service = new DocumentReviewService;

    $orig = '<p>Analisis metode komputasi awal.</p>';
    $mod = '<p>Analisis metode kecerdasan buatan komputasi awal modern.</p>';

    $diff = $service->compareDocuments($orig, $mod, 'Profesor');

    expect($diff['success'])->toBeTrue()
        ->and($diff['summary']['insertions'])->toBeGreaterThan(0)
        ->and($diff['diffHtml'])->toContain('data-revision-type="insert"')
        ->and($diff['diffHtml'])->toContain('kecerdasan buatan');
});

test('white-box: extractComments identifies anchored comment spans and metadata', function () {
    $service = new DocumentReviewService;

    $html = '<p>Teks normal <span class="comment-anchor" data-comment-id="c-1" data-comment-text="Perjelas rujukan" data-comment-author="Dr. Ani" data-comment-resolved="false">bagian ditinjau</span> lanjutan teks.</p>';

    $comments = $service->extractComments($html);
    expect($comments)->toHaveCount(1)
        ->and($comments[0]['id'])->toBe('c-1')
        ->and($comments[0]['text'])->toBe('Perjelas rujukan')
        ->and($comments[0]['author'])->toBe('Dr. Ani')
        ->and($comments[0]['resolved'])->toBeFalse()
        ->and($comments[0]['anchorText'])->toBe('bagian ditinjau');
});
