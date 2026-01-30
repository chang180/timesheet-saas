<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can view global landing page', function () {
    $page = visit(route('landing.global'));

    $page->assertSee('週報通 Timesheet SaaS')
        ->assertSee('立即建立週報帳號')
        ->assertSee('已有帳號？直接登入')
        ->assertNoJavascriptErrors();
});

it('can navigate to register page via CTA button', function () {
    $page = visit(route('landing.global'));

    $page->assertSee('立即建立週報帳號')
        ->assertNoJavascriptErrors();

    // Click the register CTA button
    $page->click('立即建立週報帳號')
        ->assertNoJavascriptErrors();

    // Should navigate to register page via Inertia
    $page->wait(1);

    // Verify we're on the register page
    $page->assertSee('建立週報工作簿')
        ->assertNoJavascriptErrors();
});

it('can navigate to login page via login link', function () {
    $page = visit(route('landing.global'));

    $page->assertSee('已有帳號？直接登入')
        ->assertNoJavascriptErrors();

    // Click the login link
    $page->click('已有帳號？直接登入')
        ->assertNoJavascriptErrors();

    // Should navigate to login page via Inertia
    $page->wait(1);

    // Verify we're on the login page
    $page->assertSee('登入週報通')
        ->assertNoJavascriptErrors();
});

it('shows welcome showcase demo on landing page', function () {
    $page = visit(route('landing.global'));

    // WelcomeShowcase should be visible in the demo section
    $page->assertSee('週報通 Timesheet SaaS')
        ->assertSee('核心功能')
        ->assertSee('週報管理')
        ->assertSee('快速上手')
        ->assertNoJavascriptErrors();
});

it('displays feature cards correctly', function () {
    $page = visit(route('landing.global'));

    $page->assertSee('週報管理')
        ->assertSee('Redmine/Jira 整合')
        ->assertSee('層級管理')
        ->assertSee('IP 白名單')
        ->assertSee('統計報表')
        ->assertSee('假日提醒')
        ->assertNoJavascriptErrors();
});

it('shows quick start steps', function () {
    $page = visit(route('landing.global'));

    $page->assertSee('快速上手')
        ->assertSee('建立用戶帳號')
        ->assertSee('設定組織層級')
        ->assertSee('開始填寫週報')
        ->assertNoJavascriptErrors();
});

it('preserves spa structure during navigation', function () {
    $page = visit(route('landing.global'));

    // Record initial app element to verify SPA behavior
    $page->assertSee('週報通 Timesheet SaaS')
        ->assertNoJavascriptErrors();

    // Navigate to login
    $page->click('已有帳號？直接登入')
        ->wait(1)
        ->assertNoJavascriptErrors();

    // Verify login page loaded
    $page->assertSee('登入週報通');

    // Navigate back to landing using browser back
    $page->navigate(route('landing.global'))
        ->wait(1)
        ->assertNoJavascriptErrors();

    // Should still see landing page content without full reload
    $page->assertSee('週報通 Timesheet SaaS')
        ->assertNoJavascriptErrors();
});

it('shows demo tenant section when enabled', function () {
    config()->set('landing.demo_tenant.enabled', true);
    config()->set('landing.demo_tenant.name', 'Demo 用戶');
    config()->set('landing.demo_tenant.url', 'https://demo.test');
    config()->set('landing.demo_tenant.description', '即刻體驗週報通');

    $page = visit(route('landing.global'));

    $page->assertSee('體驗 Demo 用戶')
        ->assertSee('進入 Demo 用戶')
        ->assertNoJavascriptErrors();
});
