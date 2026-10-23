<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\ElectionController as AdminElection;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\ReportController as AdminReport;
use App\Http\Controllers\Admin\ReviewController as AdminReview;
use App\Http\Controllers\Admin\UserController as AdminUser;
use App\Http\Controllers\Observer\DashboardController as ObserverDashboard;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboard;
use App\Http\Controllers\Officer\ReportController as OfficerReport;
use App\Http\Controllers\Officer\ReviewController as OfficerReview;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicVerificationController;
use App\Http\Controllers\RedirectController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC VERIFICATION
|--------------------------------------------------------------------------
*/

Route::get('/', [
    PublicVerificationController::class,
    'home',
])->name('home');

Route::get('/verify', [
    PublicVerificationController::class,
    'index',
])->name('verify.index');

Route::get('/verify/results/{result}', [
    PublicVerificationController::class,
    'show',
])->name('verify.show');

/*
|--------------------------------------------------------------------------
| Legacy public results routes
|--------------------------------------------------------------------------
|
| Keep these so existing links don't immediately break.
|
*/

Route::get('/results', [
    PublicVerificationController::class,
    'legacyIndex',
])->name('results.index');

Route::get('/results/{result}', [
    PublicVerificationController::class,
    'show',
])->name('results.show');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED DASHBOARD REDIRECT
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', RedirectController::class)
    ->middleware('auth')
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| PROFILE
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy',
    ])->name('profile.destroy');
});


/*
|--------------------------------------------------------------------------
| SYSTEM ADMINISTRATOR
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:ADMIN',
])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [
            AdminDashboard::class,
            'index',
        ])->name('dashboard');

        /*
        | Elections
        */
        Route::resource(
            'elections',
            AdminElection::class
        );

        Route::post(
            'elections/{election}/archive',
            [AdminElection::class, 'archive']
        )->name('elections.archive');

        /*
        | Users
        */
        Route::resource(
            'users',
            AdminUser::class
        )->except(['show']);

        /*
        | Import
        */
        Route::get('/import', [
            ImportController::class,
            'index',
        ])->name('import');

        Route::post('/import', [
            ImportController::class,
            'store',
        ])->name('import.store');

        /*
        | Results
        */
        Route::get('/results', [
            AdminReview::class,
            'results',
        ])->name('results');

        Route::get('/flagged', [
            AdminReview::class,
            'flagged',
        ])->name('flagged');

        Route::get('/results/{result}', [
            AdminReview::class,
            'show',
        ])->name('results.show');

        Route::post('/results/{result}/review', [
            AdminReview::class,
            'review',
        ])->name('results.review');

        /*
        | Reports
        */
        Route::get('/reports', [
            AdminReport::class,
            'index',
        ])->name('reports.index');

        Route::get('/reports/export', [
            AdminReport::class,
            'export',
        ])->name('reports.export');

        /*
        | Audit logs
        */
        Route::get('/audit-logs', [
            AdminDashboard::class,
            'auditLogs',
        ])->name('audit');
    });


/*
|--------------------------------------------------------------------------
| ELECTION OFFICER
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:ELECTION_OFFICER',
])
    ->prefix('officer')
    ->name('officer.')
    ->group(function () {

        Route::get('/', [
            OfficerDashboard::class,
            'index',
        ])->name('dashboard');

        Route::get('/elections', [
            OfficerDashboard::class,
            'elections',
        ])->name('elections');

        Route::get('/elections/{election}', [
            OfficerDashboard::class,
            'electionShow',
        ])->name('elections.show');

        Route::get('/results', [
            OfficerReview::class,
            'results',
        ])->name('results');

        Route::get('/review-queue', [
            OfficerReview::class,
            'queue',
        ])->name('queue');

        Route::get('/results/{result}', [
            OfficerReview::class,
            'show',
        ])->name('results.show');

        Route::post('/results/{result}/review', [
            OfficerReview::class,
            'review',
        ])->name('results.review');

        Route::get('/reports', [
            OfficerReport::class,
            'index',
        ])->name('reports.index');

        Route::get('/reports/export', [
            OfficerReport::class,
            'export',
        ])->name('reports.export');
    });


/*
|--------------------------------------------------------------------------
| ELECTION OBSERVER
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'role:OBSERVER',
])
    ->prefix('observer')
    ->name('observer.')
    ->group(function () {

        Route::get('/', [
            ObserverDashboard::class,
            'index',
        ])->name('dashboard');

        Route::get('/elections', [
            ObserverDashboard::class,
            'elections',
        ])->name('elections');

        Route::get('/elections/{election}', [
            ObserverDashboard::class,
            'electionShow',
        ])->name('elections.show');

        Route::get('/results', [
            ObserverDashboard::class,
            'results',
        ])->name('results');

        Route::get('/results/{result}', [
            ObserverDashboard::class,
            'show',
        ])->name('results.show');
    });


require __DIR__ . '/auth.php';
