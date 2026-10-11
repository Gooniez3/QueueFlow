<?php

use App\Http\Controllers\CustomerBranchController;
use App\Http\Controllers\CustomerBusinessController;
use App\Http\Controllers\CustomerExperienceController;
use App\Http\Controllers\CustomerHomeController;
use App\Http\Controllers\CustomerQueueEntryController;
use App\Http\Controllers\CustomerServiceController;
use App\Http\Controllers\CustomerTicketController;
use App\Http\Controllers\QueueBoardController;
use App\Http\Controllers\Staff\AuthenticatedSessionController;
use App\Http\Controllers\Staff\BranchController;
use App\Http\Controllers\Staff\BusinessController;
use App\Http\Controllers\Staff\LiveQueueController;
use App\Http\Controllers\Staff\QueueController as StaffQueueController;
use App\Http\Controllers\Staff\QueueEntryQrController;
use App\Http\Controllers\Staff\RealtimeController;
use App\Http\Controllers\Staff\RegisteredStaffController;
use App\Http\Controllers\Staff\ServiceController;
use App\Http\Controllers\Staff\StaffHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', CustomerHomeController::class)->name('home');
Route::get('/places', [CustomerExperienceController::class, 'places'])->name('places.index');
Route::get('/places/{category}', [CustomerExperienceController::class, 'category'])
    ->where('category', '[a-z-]+')
    ->name('places.show');
Route::get('/account', [CustomerExperienceController::class, 'account'])->name('account.show');
Route::get('/more', [CustomerExperienceController::class, 'more'])->name('more.show');
Route::get('/scanner', [CustomerExperienceController::class, 'scanner'])->name('scanner.show');
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
Route::post('/queues/{queueId}/entries/{entryId}/cancel', [CustomerQueueEntryController::class, 'cancel'])
    ->whereNumber(['queueId', 'entryId'])
    ->name('queue-entries.cancel');
Route::get('/queues/{publicCode}/board', [QueueBoardController::class, 'show'])
    ->where('publicCode', '[A-Za-z0-9-]+')
    ->name('queues.board.show');
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

        Route::get('/live-queues', [LiveQueueController::class, 'gateway'])
            ->name('live-queues.gateway');

        Route::get('/branches', [BranchController::class, 'index'])
            ->name('branches.index');

        Route::get('/services', [ServiceController::class, 'index'])
            ->name('services.index');

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
            Route::get('/businesses/{businessId}/edit', [BusinessController::class, 'edit'])
                ->whereNumber('businessId')
                ->name('businesses.edit');
            Route::put('/businesses/{businessId}', [BusinessController::class, 'update'])
                ->whereNumber('businessId')
                ->name('businesses.update');

            Route::get('/businesses/{businessId}/branches/create', [BranchController::class, 'create'])
                ->whereNumber('businessId')
                ->name('branches.create');
            Route::post('/businesses/{businessId}/branches', [BranchController::class, 'store'])
                ->whereNumber('businessId')
                ->name('branches.store');
            Route::get('/businesses/{businessId}/branches/{branchId}', [BranchController::class, 'show'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('branches.show');
            Route::get('/businesses/{businessId}/branches/{branchId}/edit', [BranchController::class, 'edit'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('branches.edit');
            Route::put('/businesses/{businessId}/branches/{branchId}', [BranchController::class, 'update'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('branches.update');

            Route::get('/businesses/{businessId}/branches/{branchId}/queues', [LiveQueueController::class, 'index'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('live-queues.index');

            Route::get('/businesses/{businessId}/branches/{branchId}/queue-entry-qr', [QueueEntryQrController::class, 'create'])
                ->whereNumber(['businessId', 'branchId'])
                ->middleware('queueflow.branch.member')
                ->name('queue-entry-qr.create');
            Route::post('/businesses/{businessId}/branches/{branchId}/queue-entry-qr', [QueueEntryQrController::class, 'verify'])
                ->whereNumber(['businessId', 'branchId'])
                ->middleware('queueflow.branch.member')
                ->name('queue-entry-qr.verify');

            Route::get('/businesses/{businessId}/branches/{branchId}/events', [RealtimeController::class, 'branch'])
                ->whereNumber(['businessId', 'branchId'])
                ->middleware('queueflow.branch.member')
                ->name('live-queues.events');

            Route::get('/businesses/{businessId}/branches/{branchId}/queues/create', [StaffQueueController::class, 'create'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('live-queues.create');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues', [StaffQueueController::class, 'store'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('live-queues.store');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/pause', [LiveQueueController::class, 'pause'])
                ->whereNumber(['businessId', 'branchId', 'queueId'])
                ->name('live-queues.pause');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/resume', [LiveQueueController::class, 'resume'])
                ->whereNumber(['businessId', 'branchId', 'queueId'])
                ->name('live-queues.resume');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/close', [LiveQueueController::class, 'close'])
                ->whereNumber(['businessId', 'branchId', 'queueId'])
                ->name('live-queues.close');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/reopen', [LiveQueueController::class, 'reopen'])
                ->whereNumber(['businessId', 'branchId', 'queueId'])
                ->name('live-queues.reopen');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/call-next', [LiveQueueController::class, 'callNext'])
                ->whereNumber(['businessId', 'branchId', 'queueId'])
                ->name('live-queues.call-next');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/entries/{entryId}/start', [LiveQueueController::class, 'startServing'])
                ->whereNumber(['businessId', 'branchId', 'queueId', 'entryId'])
                ->name('live-queues.start');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/entries/{entryId}/recall', [LiveQueueController::class, 'recall'])
                ->whereNumber(['businessId', 'branchId', 'queueId', 'entryId'])
                ->name('live-queues.recall');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/entries/{entryId}/skip', [LiveQueueController::class, 'skip'])
                ->whereNumber(['businessId', 'branchId', 'queueId', 'entryId'])
                ->name('live-queues.skip');

            Route::post('/businesses/{businessId}/branches/{branchId}/queues/{queueId}/entries/{entryId}/complete', [LiveQueueController::class, 'complete'])
                ->whereNumber(['businessId', 'branchId', 'queueId', 'entryId'])
                ->name('live-queues.complete');

            Route::get('/businesses/{businessId}/branches/{branchId}/services/create', [ServiceController::class, 'create'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('services.create');
            Route::post('/businesses/{businessId}/branches/{branchId}/services', [ServiceController::class, 'store'])
                ->whereNumber(['businessId', 'branchId'])
                ->name('services.store');
            Route::get('/businesses/{businessId}/branches/{branchId}/services/{serviceId}', [ServiceController::class, 'show'])
                ->whereNumber(['businessId', 'branchId', 'serviceId'])
                ->name('services.show');
            Route::get('/businesses/{businessId}/branches/{branchId}/services/{serviceId}/edit', [ServiceController::class, 'edit'])
                ->whereNumber(['businessId', 'branchId', 'serviceId'])
                ->name('services.edit');
            Route::put('/businesses/{businessId}/branches/{branchId}/services/{serviceId}', [ServiceController::class, 'update'])
                ->whereNumber(['businessId', 'branchId', 'serviceId'])
                ->name('services.update');
        });
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
