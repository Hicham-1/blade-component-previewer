<?php

namespace H1ch4m\BladeComponentPreviewer;

use Illuminate\Support\ServiceProvider;

class BladeComponentPreviewerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'h1ch4m');

        $this->publishes([
            __DIR__ . '/../config/blade-component-previewer.php' => config_path('blade-component-previewer.php'),
        ], 'config');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/blade-component-previewer.php',
            'blade-component-previewer'
        );
    }
}
