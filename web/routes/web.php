<?php

use App\Http\Controllers\CustomerHomeController;
use App\Http\Controllers\CustomerTicketController;
use App\Http\Controllers\QueueBoardController;
use App\Http\Controllers\Staff\AuthenticatedSessionController;
use App\Http\Controllers\Staff\RegisteredStaffController;
use App\Http\Controllers\Staff\StaffHomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', CustomerHomeController::class)->name('home');
Route::get('/queue-board', QueueBoardController::class)->name('queue-board.show');
Route::get('/ticket', CustomerTicketController::class)->name('tickets.show');

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
    Route::get('/', StaffHomeController::class)
        ->middleware('queueflow.staff.auth')
        ->name('home');
});
