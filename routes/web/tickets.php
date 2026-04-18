<?php

use App\Http\Controllers\Tickets\PublicTicketsController;
use App\Http\Controllers\Tickets\TicketsController;
use Illuminate\Support\Facades\Route;

Route::resource('tickets',
    TicketsController::class,
    ['middleware' => ['auth']]
)->parameters(['tickets' => 'ticket'])->withTrashed();

Route::group([
    'prefix' => 'tickets',
    'middleware' => ['auth'],
], function () {
    Route::post('{ticket}/restore', [TicketsController::class, 'restore'])
        ->name('tickets.restore')
        ->withTrashed();
    Route::post('{ticket}/comments', [TicketsController::class, 'storeComment'])
        ->name('tickets.comments.store');
    Route::post('{ticket}/worklogs', [TicketsController::class, 'storeWorklog'])
        ->name('tickets.worklogs.store');
});

Route::group([
    'prefix' => 'helpdesk/{tenantPortal}',
    'as' => 'tickets.portal.',
    'middleware' => ['throttle:api'],
], function () {
    Route::get('/', [PublicTicketsController::class, 'create'])->name('create');
    Route::post('/', [PublicTicketsController::class, 'store'])->name('store');
    Route::get('/tickets/{ticket}/{token}', [PublicTicketsController::class, 'show'])->name('show');
    Route::post('/tickets/{ticket}/{token}/reply', [PublicTicketsController::class, 'reply'])->name('reply');
    Route::get('/tickets/{ticket}/{token}/files/{fileId}', [PublicTicketsController::class, 'downloadFile'])->name('files.download');
});
