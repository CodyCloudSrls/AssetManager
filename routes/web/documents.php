<?php

use App\Http\Controllers\Documents\DocumentsController;
use App\Models\Document;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

Route::group(
    [
        'prefix' => 'documents',
        'middleware' => ['auth'],
    ],
    function () {
        Route::post('{document}/restore', [DocumentsController::class, 'restore'])
            ->name('documents.restore')
            ->withTrashed();
    }
);

Route::resource('documents',
    DocumentsController::class,
    ['middleware' => ['auth']]
)->parameters(['documents' => 'document'])->withTrashed();

