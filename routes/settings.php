<?php

use App\Http\Controllers\Settings\BusinessHoursController;
use App\Http\Controllers\Settings\IssueChecklistTemplateController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SlaLevelController;
/* @chisel-password-confirmation */
use Illuminate\Auth\Middleware\RequirePassword;
/* @end-chisel-password-confirmation */
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        /* @chisel-password-confirmation */
        ->middleware(RequirePassword::class)
        /* @end-chisel-password-confirmation */
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');

    Route::get('settings/werkuren', [BusinessHoursController::class, 'edit'])->name('business-hours.edit');
    Route::patch('settings/werkuren', [BusinessHoursController::class, 'update'])->name('business-hours.update');

    Route::get('settings/issue-checklist', [IssueChecklistTemplateController::class, 'index'])
        ->name('issue-checklist.index');
    Route::post('settings/issue-checklist', [IssueChecklistTemplateController::class, 'store'])
        ->name('issue-checklist.store');
    Route::patch('settings/issue-checklist/{template}', [IssueChecklistTemplateController::class, 'update'])
        ->name('issue-checklist.update');
    Route::patch('settings/issue-checklist/{template}/move', [IssueChecklistTemplateController::class, 'move'])
        ->name('issue-checklist.move');
    Route::delete('settings/issue-checklist/{template}', [IssueChecklistTemplateController::class, 'destroy'])
        ->name('issue-checklist.destroy');

    Route::get('settings/sla-levels', [SlaLevelController::class, 'index'])->name('sla-levels.index');
    Route::post('settings/sla-levels', [SlaLevelController::class, 'store'])->name('sla-levels.store');
    Route::patch('settings/sla-levels/{slaLevel}', [SlaLevelController::class, 'update'])->name('sla-levels.update');
    Route::delete('settings/sla-levels/{slaLevel}', [SlaLevelController::class, 'destroy'])->name('sla-levels.destroy');
});

/* @chisel-passkeys */
Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
/* @end-chisel-passkeys */
