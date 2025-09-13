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


    Route::get('/public/{type}/{file}', function ($type, $file) {
        $path = __DIR__ . "/../public/{$type}/{$file}";

        if (!file_exists($path)) {
            abort(404);
        }

        $contentType = $type === 'style' ? 'text/css' : 'application/javascript';
        return response()->file($path, [
            'Content-Type' => $contentType,
        ]);
    });
});
