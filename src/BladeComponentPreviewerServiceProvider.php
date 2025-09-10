<?php

namespace H1ch4m\BladeComponentPreviewer;

use Illuminate\Support\ServiceProvider;

class BladeComponentPreviewerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'h1ch4m');
    }

    public function register(): void {}
}
