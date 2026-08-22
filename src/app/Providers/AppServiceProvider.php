<?php

namespace App\Providers;

use App\Services\GeminiResumeSummaryProvider;
use App\Services\ResumeSummaryProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Vite;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ResumeSummaryProvider::class, GeminiResumeSummaryProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::createAssetPathsUsing(
            fn(string $path): string => '/' . ltrim($path, '/')
        );
    }
}
