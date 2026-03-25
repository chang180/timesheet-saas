<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('agentic-team:run generates orchestrator entry output', function () {
    $this->artisan('agentic-team:run', [
        'epicId' => 'EPIC-01-doc-alignment',
    ])
        ->expectsOutputToContain('=== Agentic Team：Orchestrator 入場訊息已生成 ===')
        ->expectsOutputToContain('史詩 ID： EPIC-01-doc-alignment')
        ->assertExitCode(0);

    $dir = storage_path('app/agentic-team');
    $files = \Illuminate\Support\Facades\File::glob($dir.'/orchestrator-entry-epic-01-doc-alignment-*.md');

    expect($files)->not->toBeEmpty();
});
