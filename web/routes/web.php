<?php

use App\Http\Controllers\CustomerHomeController;
use App\Http\Controllers\CustomerTicketController;
use App\Http\Controllers\QueueBoardController;
use Illuminate\Support\Facades\Route;

Route::get('/', CustomerHomeController::class)->name('home');
Route::get('/queue-board', QueueBoardController::class)->name('queue-board.show');
Route::get('/ticket', CustomerTicketController::class)->name('tickets.show');
