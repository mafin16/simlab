<?php

namespace App\Providers;

use App\Models\Lab;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        view()->composer('layouts.navigation', function ($view) {
            $labs = Lab::withCount(['assets' => fn ($q) => $q->where('category', 'PC Desktop')])
                ->orderBy('id')
                ->get();
            $view->with('labs', $labs);
        });
    }
}
