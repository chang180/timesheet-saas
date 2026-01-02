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

                    // 組織層級設定
                    Route::get('settings/organization-levels', [\App\Http\Controllers\Tenant\OrganizationLevelsController::class, 'show'])->name('settings.organization-levels.show');
                    Route::put('settings/organization-levels', [\App\Http\Controllers\Tenant\OrganizationLevelsController::class, 'update'])->name('settings.organization-levels.update');

                    // 成員管理
                    Route::get('members', [\App\Http\Controllers\Tenant\MemberController::class, 'index'])->name('members.index');
                    Route::post('members/invite', [MemberInviteController::class, 'store'])->name('members.invite');
                    Route::patch('members/{member}/roles', [MemberRoleController::class, 'update'])->name('members.roles.update');
                    Route::post('members/{member}/approve', [MemberApprovalController::class, 'store'])->name('members.approve');

                    // 邀請接受
                    Route::post('auth/invitations/accept', [\App\Http\Controllers\Tenant\InvitationAcceptController::class, 'store'])->name('auth.invitations.accept');

                    // 組織層級管理
                    Route::apiResource('divisions', \App\Http\Controllers\Tenant\DivisionController::class);
                    Route::apiResource('departments', \App\Http\Controllers\Tenant\DepartmentController::class);
                    Route::apiResource('teams', \App\Http\Controllers\Tenant\TeamController::class);

                    // 組織層級邀請連結管理
                    Route::get('divisions/{division}/invitation', [\App\Http\Controllers\Tenant\DivisionInvitationController::class, 'show'])->name('divisions.invitation.show');
                    Route::post('divisions/{division}/invitation/generate', [\App\Http\Controllers\Tenant\DivisionInvitationController::class, 'generate'])->name('divisions.invitation.generate');
                    Route::patch('divisions/{division}/invitation/toggle', [\App\Http\Controllers\Tenant\DivisionInvitationController::class, 'toggle'])->name('divisions.invitation.toggle');

                    Route::get('departments/{department}/invitation', [\App\Http\Controllers\Tenant\DepartmentInvitationController::class, 'show'])->name('departments.invitation.show');
                    Route::post('departments/{department}/invitation/generate', [\App\Http\Controllers\Tenant\DepartmentInvitationController::class, 'generate'])->name('departments.invitation.generate');
                    Route::patch('departments/{department}/invitation/toggle', [\App\Http\Controllers\Tenant\DepartmentInvitationController::class, 'toggle'])->name('departments.invitation.toggle');

                    Route::get('teams/{team}/invitation', [\App\Http\Controllers\Tenant\TeamInvitationController::class, 'show'])->name('teams.invitation.show');
                    Route::post('teams/{team}/invitation/generate', [\App\Http\Controllers\Tenant\TeamInvitationController::class, 'generate'])->name('teams.invitation.generate');
                    Route::patch('teams/{team}/invitation/toggle', [\App\Http\Controllers\Tenant\TeamInvitationController::class, 'toggle'])->name('teams.invitation.toggle');
                });
            });
    });
