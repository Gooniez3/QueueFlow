<?php

use App\Http\Controllers\CustomerBranchController;
use App\Http\Controllers\CustomerBusinessController;
use App\Http\Controllers\CustomerHomeController;
use App\Http\Controllers\CustomerQueueEntryController;
use App\Http\Controllers\CustomerServiceController;
use App\Http\Controllers\CustomerTicketController;
use App\Http\Controllers\QueueBoardController;
use App\Http\Controllers\Staff\AuthenticatedSessionController;
use App\Http\Controllers\Staff\BranchController;
use App\Http\Controllers\Staff\BusinessController;
use App\Http\Controllers\Staff\RegisteredStaffController;
use App\Http\Controllers\Staff\ServiceController;
use App\Http\Controllers\Staff\StaffHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', CustomerHomeController::class)->name('home');
Route::get('/businesses/{businessId}', [CustomerBusinessController::class, 'show'])
    ->whereNumber('businessId')
    ->name('businesses.show');
Route::get('/businesses/{businessId}/branches/{branchId}', [CustomerBranchController::class, 'show'])
    ->whereNumber(['businessId', 'branchId'])
    ->name('branches.show');
Route::get('/businesses/{businessId}/branches/{branchId}/services/{serviceId}', [CustomerServiceController::class, 'show'])
    ->whereNumber(['businessId', 'branchId', 'serviceId'])
    ->name('services.show');
Route::post('/queues/{queueId}/entries', [CustomerQueueEntryController::class, 'store'])
    ->whereNumber('queueId')
    ->name('queue-entries.store');
Route::get('/queues/{queueId}/entries/{entryId}', [CustomerTicketController::class, 'show'])
    ->whereNumber(['queueId', 'entryId'])
    ->name('queue-entries.show');
Route::get('/queue-board', QueueBoardController::class)->name('queue-board.show');
Route::get('/ticket', [CustomerTicketController::class, 'index'])->name('tickets.show');

Route::prefix('staff')->name('staff.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/register', [RegisteredStaffController::class, 'create'])
        ->name('register');
    Route::post('/register', [RegisteredStaffController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('register.store');

    Route::middleware('queueflow.staff.auth')->group(function (): void {
        Route::get('/', StaffHomeController::class)
            ->name('home');

        Route::get('/businesses', [BusinessController::class, 'index'])
            ->name('businesses.index');
        Route::get('/businesses/create', [BusinessController::class, 'create'])
            ->name('businesses.create');
        Route::post('/businesses', [BusinessController::class, 'store'])
            ->name('businesses.store');

        Route::middleware('queueflow.business.member')->group(function (): void {
            Route::get('/businesses/{businessId}', [BusinessController::class, 'show'])
                ->whereNumber('businessId')
                ->name('businesses.show');

            Route::get('/businesses/{businessId}/branches/create', [BranchController::class, 'create'])
                ->whereNumber('businessId')
                ->name('branches.create');
            Route::post('/businesses/{businessId}/branches', [BranchController::class, 'store'])
                ->whereNumber('businessId')
                ->name('branches.store');
            Route::get('/businesses/{businessId}/branches/{branchId}', [BranchController::class, 'show'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('branches.show');

            Route::get('/businesses/{businessId}/branches/{branchId}/services/create', [ServiceController::class, 'create'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('services.create');
            Route::post('/businesses/{businessId}/branches/{branchId}/services', [ServiceController::class, 'store'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('services.store');
            Route::get('/businesses/{businessId}/branches/{branchId}/services/{serviceId}', [ServiceController::class, 'show'])
                ->whereNumber(['businessId', 'branchId', 'serviceId'])
                ->name('services.show');
        });
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
