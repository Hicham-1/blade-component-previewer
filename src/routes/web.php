<?php

use H1ch4m\BladeComponentPreviewer\controllers\BladeComponentPreviewerController as BCP;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'blade-component-previewer', 'as' => 'blade-component-previewer.'], function () {
    Route::get('/', BCP::class)->name('index');
});
