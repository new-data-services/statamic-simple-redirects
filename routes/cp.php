<?php

use Illuminate\Support\Facades\Route;
use Ndx\SimpleRedirect\Http\Controllers\RedirectController;
use Ndx\SimpleRedirect\Http\Controllers\RedirectTreeController;

Route::prefix('redirects')->name('simple-redirects.')->group(function () {
    Route::get('/', [RedirectController::class, 'index'])->name('index');
    Route::get('/create', [RedirectController::class, 'create'])->name('create');
    Route::post('/', [RedirectController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [RedirectController::class, 'edit'])->name('edit');
    Route::patch('/{id}', [RedirectController::class, 'update'])->name('update');
    Route::delete('/{id}', [RedirectController::class, 'destroy'])->name('destroy');

    Route::post('/tree', [RedirectTreeController::class, 'update'])->name('tree.update');
});
