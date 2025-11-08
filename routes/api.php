<?php

use App\Http\Controllers\Tenant\IpWhitelistController;
use App\Http\Controllers\Tenant\MemberApprovalController;
use App\Http\Controllers\Tenant\MemberInviteController;
use App\Http\Controllers\Tenant\MemberRoleController;
use App\Http\Controllers\Tenant\SettingsController;
use App\Http\Controllers\Tenant\WelcomePageController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->group(function (): void {
        Route::prefix('hq')
            ->as('hq.')
            ->middleware(['auth:sanctum'])
            ->group(function (): void {
                //
            });

        Route::prefix('{company:slug}')
            ->as('tenant.')
            ->middleware(['tenant'])
            ->group(function (): void {
                Route::middleware(['auth:sanctum'])->group(function (): void {
                    Route::get('settings', [SettingsController::class, 'show'])->name('settings.show');
                    Route::put('welcome-page', [WelcomePageController::class, 'update'])->name('welcome-page.update');
                    Route::put('settings/ip-whitelist', [IpWhitelistController::class, 'update'])->name('settings.ip-whitelist.update');
                    Route::post('members/invite', [MemberInviteController::class, 'store'])->name('members.invite');
                    Route::patch('members/{member}/roles', [MemberRoleController::class, 'update'])->name('members.roles.update');
                    Route::post('members/{member}/approve', [MemberApprovalController::class, 'store'])->name('members.approve');
                });
            });
    });
