<?php

use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Http\Controllers\ImportExportController;
use Ndx\SimpleRedirect\Http\Controllers\RedirectActionController;
use Ndx\SimpleRedirect\Http\Controllers\RedirectController;

Route::prefix('redirects')->name('simple-redirects.')->group(function () {
    Route::get('/', [RedirectController::class, 'index'])->name('index');
    Route::get('/create', [RedirectController::class, 'create'])->name('create');
    Route::post('/', [RedirectController::class, 'store'])->name('store');

    Route::post('/reorder', [RedirectController::class, 'reorder'])->name('reorder');

    Route::get('/export', [ImportExportController::class, 'export'])->name('export');
    Route::post('/import', [ImportExportController::class, 'import'])->name('import');

    Route::post('/actions/list', [RedirectActionController::class, 'bulkActions'])->name('actions.bulk');
    Route::post('/actions', [RedirectActionController::class, 'run'])->name('actions.run');

    Route::get('/{id}', [RedirectController::class, 'edit'])->name('edit');
    Route::patch('/{id}', [RedirectController::class, 'update'])->name('update');
    Route::delete('/{id}', [RedirectController::class, 'destroy'])->name('destroy');
});
