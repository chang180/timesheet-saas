<?php

use App\Models\User;
use App\Models\WeeklyReport;

function makePersonalReport(User $user, array $overrides = []): WeeklyReport
{
    return WeeklyReport::create([
        'company_id' => null,
        'user_id' => $user->id,
        'work_year' => 2026,
        'work_week' => 17,
        'status' => WeeklyReport::STATUS_DRAFT,
        ...$overrides,
    ]);
}

test('draft report cannot be made public', function () {
    $user = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    $report = makePersonalReport($user);

    $response = $this->actingAs($user)->from(route('personal.weekly-reports.edit', $report))
        ->post(route('personal.weekly-reports.toggle-public', $report), ['is_public' => true]);

    $response->assertSessionHasErrors(['is_public']);
    expect($report->fresh()->is_public)->toBeFalse();
});

test('submitted report can be made public; published_at is set once', function () {
    $user = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    $report = makePersonalReport($user, ['status' => WeeklyReport::STATUS_SUBMITTED]);

    $this->actingAs($user)->post(route('personal.weekly-reports.toggle-public', $report), ['is_public' => true])
        ->assertRedirect(route('personal.weekly-reports.edit', $report));

    $fresh = $report->fresh();
    expect($fresh->is_public)->toBeTrue();
    expect($fresh->published_at)->not->toBeNull();

    // Toggle off, published_at persists
    $firstPublishedAt = $fresh->published_at;
    $this->actingAs($user)->post(route('personal.weekly-reports.toggle-public', $report), ['is_public' => false]);

    $fresh2 = $report->fresh();
    expect($fresh2->is_public)->toBeFalse();
    expect($fresh2->published_at?->toIso8601String())->toBe($firstPublishedAt->toIso8601String());

    // Toggle back on; published_at stays as the earlier value
    $this->actingAs($user)->post(route('personal.weekly-reports.toggle-public', $report), ['is_public' => true]);
    $fresh3 = $report->fresh();
    expect($fresh3->is_public)->toBeTrue();
    expect($fresh3->published_at?->toIso8601String())->toBe($firstPublishedAt->toIso8601String());
});

test('user without handle cannot publish', function () {
    $user = User::factory()->create(['company_id' => null, 'handle' => null]);
    $report = makePersonalReport($user, ['status' => WeeklyReport::STATUS_SUBMITTED]);

    $response = $this->actingAs($user)->from(route('personal.weekly-reports.edit', $report))
        ->post(route('personal.weekly-reports.toggle-public', $report), ['is_public' => true]);

    $response->assertSessionHasErrors(['is_public']);
    expect($report->fresh()->is_public)->toBeFalse();
});

test('another user cannot toggle someone elses report', function () {
    $alice = User::factory()->create(['company_id' => null, 'handle' => 'alice']);
    $bob = User::factory()->create(['company_id' => null, 'handle' => 'bob']);
    $report = makePersonalReport($alice, ['status' => WeeklyReport::STATUS_SUBMITTED]);

    $this->actingAs($bob)->post(route('personal.weekly-reports.toggle-public', $report), ['is_public' => true])
        ->assertForbidden();
});
