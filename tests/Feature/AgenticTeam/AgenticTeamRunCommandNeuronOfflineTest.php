<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('agentic-team:run --neuron --offline saves deterministic neuron output', function () {
    $epicId = 'EPIC-01-doc-alignment';

    $this->artisan('agentic-team:run', [
        'epicId' => $epicId,
        '--neuron' => true,
        '--offline' => true,
    ])
        ->expectsOutputToContain('=== Neuron runtime: minimal decision report ===')
        ->expectsOutputToContain('# Neuron Runtime Output (OFFLINE STUB)')
        ->assertExitCode(0);

    $dir = storage_path('app/agentic-team');
    $files = \Illuminate\Support\Facades\File::glob(
        $dir.'/orchestrator-neuron-output-'.'epic-01-doc-alignment'.'-*.md'
    );

    expect($files)->not->toBeEmpty();
});
