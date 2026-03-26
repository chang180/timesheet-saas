<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('agentic-team:run --neuron --toolexec --offline saves ToolExecutorAgent output', function () {
    $epicId = 'EPIC-01-doc-alignment';

    $this->artisan('agentic-team:run', [
        'epicId' => $epicId,
        '--neuron' => true,
        '--toolexec' => true,
        '--offline' => true,
    ])
        ->expectsOutputToContain('=== ToolExecutorAgent：套用變更→測試→commit/push ===')
        ->expectsOutputToContain('# ToolExecutorAgent 執行輸出（離線 stub）')
        ->assertExitCode(0);

    $dir = storage_path('app/agentic-team');
    $files = \Illuminate\Support\Facades\File::glob(
        $dir.'/orchestrator-toolexec-output-'.'epic-01-doc-alignment'.'-*.md'
    );

    expect($files)->not->toBeEmpty();
});
