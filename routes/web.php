<?php

use App\Http\Controllers\Admin\CandidateController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupGuideController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ElectionController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ParticipationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VoterController;
use App\Http\Controllers\BallotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicResultController;
use App\Http\Controllers\VoterAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('voter.login'));
Route::get('/results/{election}', PublicResultController::class)->name('public.results.show');

Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('elections', ElectionController::class)->middleware('permission:manage elections');
    Route::get('elections/{election}/readiness', [ElectionController::class, 'readiness'])
        ->middleware('permission:manage elections')
        ->name('elections.readiness');
    Route::patch('elections/{election}/status', [ElectionController::class, 'status'])
        ->middleware('permission:manage elections')
        ->name('elections.status');

    Route::resource('positions', PositionController::class)->middleware('permission:manage positions');
    Route::resource('candidates', CandidateController::class)->middleware('permission:manage candidates');
    Route::post('candidates/import', [CandidateController::class, 'import'])
        ->middleware('permission:import candidates')
        ->name('candidates.import');
    Route::resource('voters', VoterController::class)->middleware('permission:manage voters');
    Route::patch('voters/{voter}/reset-vote', [VoterController::class, 'resetVote'])
        ->middleware('role:Super Admin')
        ->name('voters.reset-vote');
    Route::patch('voters/{voter}/reset-pin', [VoterController::class, 'resetPin'])
        ->middleware('permission:manage voters')
        ->name('voters.reset-pin');
    Route::post('voters/import', [VoterController::class, 'import'])
        ->middleware('permission:import voters')
        ->name('voters.import');
    Route::get('participation', ParticipationController::class)
        ->middleware('permission:view turnout')
        ->name('participation.index');

    Route::get('elections/{election}/results', [ResultController::class, 'show'])
        ->middleware('permission:view results')
        ->name('results.show');

    Route::get('imports', ImportController::class)
        ->middleware('permission:import voters|import candidates')
        ->name('imports.index');
    Route::get('imports/template/{type}', [ImportController::class, 'template'])
        ->middleware('permission:import voters|import candidates')
        ->name('imports.template');
    Route::get('imports/{import}/failed-rows', [ImportController::class, 'failedRows'])
        ->middleware('permission:import voters|import candidates')
        ->name('imports.failed-rows');

    Route::get('audit-logs', AuditLogController::class)
        ->middleware('permission:view audit logs')
        ->name('audit-logs.index');

    Route::get('settings', [SettingController::class, 'edit'])
        ->middleware('permission:manage settings')
        ->name('settings.edit');
    Route::put('settings', [SettingController::class, 'update'])
        ->middleware('permission:manage settings')
        ->name('settings.update');

    Route::resource('users', UserController::class)->middleware('role:Super Admin');
    Route::get('backup-guide', BackupGuideController::class)
        ->middleware('permission:manage settings|export reports')
        ->name('backup-guide');

    Route::middleware('permission:export reports')->group(function () {
        Route::get('elections/{election}/reports/results.pdf', [ReportController::class, 'resultsPdf'])->name('reports.results.pdf');
        Route::get('elections/{election}/exports/results.csv', [ReportController::class, 'resultsCsv'])->name('exports.results.csv');
        Route::get('elections/{election}/exports/voters.csv', [ReportController::class, 'votersCsv'])->name('exports.voters.csv');
        Route::get('elections/{election}/exports/voted-status.csv', [ReportController::class, 'votedStatusCsv'])->name('exports.voted-status.csv');
        Route::get('elections/{election}/exports/candidates.csv', [ReportController::class, 'candidatesCsv'])->name('exports.candidates.csv');
        Route::get('elections/{election}/reports/turnout.pdf', [ReportController::class, 'turnoutPdf'])->name('reports.turnout.pdf');
    });
});

Route::get('/voter/login', [VoterAuthController::class, 'create'])->name('voter.login');
Route::post('/voter/login', [VoterAuthController::class, 'store'])->name('voter.login.store');
Route::post('/voter/logout', [VoterAuthController::class, 'destroy'])->name('voter.logout');

Route::get('/voter/confirm', [BallotController::class, 'confirm'])->name('voter.confirm');
Route::get('/voter/ballot', [BallotController::class, 'show'])->name('voter.ballot');
Route::post('/voter/review', [BallotController::class, 'review'])->name('voter.review');
Route::post('/voter/submit', [BallotController::class, 'submit'])->name('voter.submit');

require __DIR__.'/auth.php';
