<?php

use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\WeeklyReportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
    
    // Two Factor Authentication
    Route::post('/two-factor/enable', [TwoFactorAuthenticationController::class, 'store']);
    Route::post('/two-factor/confirm', [TwoFactorAuthenticationController::class, 'update']);
    Route::delete('/two-factor/disable', [TwoFactorAuthenticationController::class, 'destroy']);
    Route::post('/two-factor/verify', [TwoFactorAuthenticationController::class, 'verify']);
    
    // Weekly Reports
    Route::apiResource('weekly-reports', WeeklyReportController::class);
    Route::get('weekly-reports/{id}/export', [WeeklyReportController::class, 'exportCsv']);
    
    // Departments
    Route::apiResource('departments', DepartmentController::class);
    Route::get('departments/{id}/summary', [DepartmentController::class, 'summary']);
});

