<?php

namespace Tests\Unit;

use App\Models\AiProvider;
use App\Services\AiService;

/*
|--------------------------------------------------------------------------
| White Box Unit Tests: AiService
|--------------------------------------------------------------------------
| Testing Wordcraft AI creative writing generation branches, style nuances,
| grammar corrections, summarizations, and prompt routing.
*/

beforeEach(function () {
    $this->aiService = new AiService;
});

test('white-box: ai service continue generates contextual sentence completions', function () {
    $resFormal = $this->aiService->process('continue', 'Berdasarkan pengamatan empiris', 'Konteks dokumen');
    expect($resFormal['action'])->toBe('continue')
        ->and($resFormal['options'])->toBeArray()
        ->and(count($resFormal['options']))->toBe(3);

    $resCreative = $this->aiService->process('continue', 'Di ujung senja', 'Konteks', '', 'creative');
    expect($resCreative['style'])->toBe('creative')
        ->and($resCreative['options'])->toBeArray()
        ->and(count($resCreative['options']))->toBe(3);
});

test('white-box: ai service elaborate enriches selection with analytical depth', function () {
    $res = $this->aiService->process('elaborate', 'efisiensi operasional');
    expect($res['action'])->toBe('elaborate')
        ->and($res['options'])->toBeArray();

    foreach ($res['options'] as $opt) {
        expect($opt)->toContain('efisiensi operasional');
    }
});

test('white-box: ai service rewrite supports multiple styles', function () {
    $styles = ['academic', 'formal', 'creative', 'concise', 'persuasive'];

    foreach ($styles as $style) {
        $res = $this->aiService->process('rewrite', 'Ini merupakan inovasi penting.', '', '', $style);
        expect($res['action'])->toBe('rewrite')
            ->and($res['style'])->toBe($style)
            ->and(count($res['options']))->toBeGreaterThanOrEqual(1);
    }
});

test('white-box: ai service grammar corrects non-standard Indonesian words to PUEBI', function () {
    $sample = 'jadual aktifitas dimana merubah sistim dan praktek harus diperbaiki';
    $res = $this->aiService->process('grammar', $sample);

    expect($res['action'])->toBe('grammar');
    $corrected = $res['options'][0];

    expect($corrected)->toContain('jadwal')
        ->and($corrected)->toContain('aktivitas')
        ->and($corrected)->toContain('di mana')
        ->and($corrected)->toContain('mengubah')
        ->and($corrected)->toContain('praktik');
});

test('white-box: ai service summarize extracts core takeaways', function () {
    $documentText = 'Dokumen pengujian implementasi editor teks web terpadu dengan standar dokumen modern.';
    $res = $this->aiService->process('summarize', $documentText);

    expect($res['action'])->toBe('summarize')
        ->and(count($res['options']))->toBeGreaterThanOrEqual(1)
        ->and($res['options'][0])->toContain('Intisari Dokumen');
});

test('white-box: ai service brainstorm proposes structural outline and critical questions', function () {
    $res = $this->aiService->process('brainstorm', '', '', 'Kecerdasan Buatan dalam Tata Kelola');

    expect($res['action'])->toBe('brainstorm')
        ->and(count($res['options']))->toBe(3)
        ->and($res['options'][0])->toContain('Ide Bagian Lanjutan');
});

test('white-box: ai service chat answers user inquiry based on active context', function () {
    $res = $this->aiService->process('chat', '', 'Dokumen ini menguji integrasi Microsoft Word dan Google Docs.', 'Format apa saja yang didukung?');

    expect($res['action'])->toBe('chat')
        ->and($res['options'][0])->toContain('Format apa saja yang didukung?')
        ->and($res['options'][0])->toContain('Berdasarkan isi dokumen');
});

test('white-box: ai service process attaches provider attribution and metadata when provider is passed', function () {
    $provider = new AiProvider([
        'uuid' => 'test-provider-uuid-1234',
        'name' => 'OpenAI GPT-4o Custom',
        'provider' => 'openai',
        'is_active' => true,
    ]);

    $res = $this->aiService->process('continue', 'Pendahuluan riset', 'Konteks dokumen', '', 'formal', $provider);

    expect($res)->toHaveKey('provider')
        ->and($res['provider'])->toBe('OpenAI GPT-4o Custom')
        ->and($res['provider_uuid'])->toBe('test-provider-uuid-1234')
        ->and($res['provider_label'])->toBe('OpenAI')
        ->and($res['explanation'])->toContain('Diproses dengan OpenAI GPT-4o Custom');
});

test('white-box: ai service process omits provider keys when provider is null', function () {
    $res = $this->aiService->process('continue', 'Pendahuluan riset', 'Konteks dokumen', '', 'formal', null);

    expect($res)->not->toHaveKey('provider')
        ->and($res)->not->toHaveKey('provider_uuid');
});
