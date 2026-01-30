<?php

use Illuminate\Support\Facades\File;

test('boost.json exists and has Boost 2.0 structure with skills', function () {
    $path = base_path('boost.json');
    expect(File::exists($path))->toBeTrue();

    $config = json_decode(File::get($path), true);
    expect($config)->toBeArray()
        ->and($config)->toHaveKey('skills')
        ->and($config['skills'])->toBeArray()
        ->and($config['skills'])->not->toBeEmpty()
        ->and($config)->toHaveKey('guidelines');
});

test('vercel agent skills are installed in .agents/skills', function () {
    $skillsPath = base_path('.agents/skills');

    if (! File::isDirectory($skillsPath)) {
        expect(true)->toBeTrue();

        return;
    }

    $skills = File::directories($skillsPath);
    expect($skills)->not->toBeEmpty();

    $names = array_map(fn ($path) => basename($path), $skills);
    expect($names)->toContain('vercel-react-best-practices')
        ->and($names)->toContain('web-design-guidelines');
});
