<?php

use App\Http\Controllers\Documents\DocumentAssignmentsController;
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
        Route::get('evidence-requests', [DocumentAssignmentsController::class, 'index'])
            ->name('documents.evidence_requests.index');

        Route::post('{document}/restore', [DocumentsController::class, 'restore'])
            ->name('documents.restore')
            ->withTrashed();

        Route::post('{document}/assignments', [DocumentAssignmentsController::class, 'store'])
            ->name('documents.assignments.store');
        Route::get('{document}/assignments/{documentAssignment}/edit', [DocumentAssignmentsController::class, 'edit'])
            ->name('documents.assignments.edit');
        Route::put('{document}/assignments/{documentAssignment}', [DocumentAssignmentsController::class, 'update'])
            ->name('documents.assignments.update');
        Route::delete('{document}/assignments/{documentAssignment}', [DocumentAssignmentsController::class, 'destroy'])
            ->name('documents.assignments.destroy');
    }
);

Route::resource('documents',
    DocumentsController::class,
    ['middleware' => ['auth']]
)->parameters(['documents' => 'document'])->withTrashed();
