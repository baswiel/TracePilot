<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\IssueReportController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ResponderController;
use App\Http\Controllers\TeamMemberController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('/', '/dashboard')->name('home');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('klanten', [CustomerController::class, 'index'])->name('customers.index');
    Route::post('klanten', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('klanten/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('klanten/{customer}/bewerken', [CustomerController::class, 'edit'])->name('customers.edit');
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
    Route::get('responders', [ResponderController::class, 'index'])->name('responders.index');
    Route::get('team', [TeamMemberController::class, 'index'])->name('team.index');
    Route::post('team', [TeamMemberController::class, 'store'])->name('team.store');
    Route::patch('team/{teamMember}', [TeamMemberController::class, 'update'])->name('team.update');
    Route::delete('team/{teamMember}', [TeamMemberController::class, 'destroy'])->name('team.destroy');
    Route::get('storingen', [IssueController::class, 'index'])->name('issues.index');
    Route::get('storingen/exporteren', [IssueController::class, 'export'])->name('issues.export');
    Route::get('rapportages', IssueReportController::class)->name('reports.index');
    Route::get('storingen/melden', [IssueController::class, 'create'])->name('issues.report');
    Route::post('storingen', [IssueController::class, 'store'])->name('issues.store');
    Route::get('storingen/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::put('storingen/{issue}', [IssueController::class, 'update'])->name('issues.update');
    Route::post('storingen/{issue}/tijdlijn', [IssueController::class, 'storeTimelineEntry'])
        ->name('issues.timeline.store');
    Route::put('storingen/{issue}/postmortem', [IssueController::class, 'updatePostmortem'])
        ->name('issues.postmortem.update');
    Route::get('storingen/{issue}/tijdlijn/{activity}/bijlage', [IssueController::class, 'downloadTimelineAttachment'])
        ->name('issues.timeline.attachment.download');
    Route::patch('storingen/{issue}/eerste-reactie', [IssueController::class, 'markFirstResponse'])
        ->name('issues.first-response.update');
    Route::patch('storingen/{issue}/checklist/{item}', [IssueController::class, 'updateChecklistItem'])
        ->name('issues.checklist.update');
});

require __DIR__.'/settings.php';
