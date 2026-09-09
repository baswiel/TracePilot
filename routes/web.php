<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('klanten', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('klanten', [CustomerController::class, 'store'])->name('customers.store');
    Route::patch('klanten/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('klanten/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('projecten', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('projecten/nieuw', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('projecten', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projecten/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('projecten/{project}/bewerken', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('projecten/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('projecten/{project}/actief', [ProjectController::class, 'updateActive'])
        ->name('projects.active.update');
    Route::get('team', [TeamMemberController::class, 'index'])->name('team.index');
    Route::post('team', [TeamMemberController::class, 'store'])->name('team.store');
    Route::patch('team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
    Route::delete('team/{teamMember}', [TeamMemberController::class, 'destroy'])->name('team.destroy');
    Route::get('storingen', [IssueController::class, 'index'])->name('issues.index');
    Route::get('storingen/melden', [IssueController::class, 'create'])->name('issues.report');
    Route::post('storingen', [IssueController::class, 'store'])->name('issues.store');
    Route::get('storingen/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::put('storingen/{issue}', [IssueController::class, 'update'])->name('issues.update');
    Route::patch('storingen/{issue}/checklist/{item}', [IssueController::class, 'updateChecklistItem'])
        ->name('issues.checklist.update');
});

require __DIR__.'/settings.php';
