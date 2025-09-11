<?php

use H1ch4m\BladeComponentPreviewer\Controllers\BCPController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'blade-component-previewer', 'as' => 'blade-component-previewer.'], function () {
    Route::get('/', [BCPController::class, 'index'])
        ->name('index');

    Route::post('/create', [BCPController::class, 'create'])
        ->name('create');

    Route::post('/update', [BCPController::class, 'update'])
        ->name('update');

    Route::post('/delete', [BCPController::class, 'delete'])
        ->name('delete');

    Route::post('/{name}/preview', [BCPController::class, 'preview'])
        ->name('preview');
});
