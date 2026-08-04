<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        if(file_exists(storage_path('installed'))){
            View::composer('admin.layouts.components.sidebar', 'App\Http\Composers\BackendMenuComposer');
            View::composer('partials._footer', 'App\Http\Composers\FrontendFooterComposer');
            View::composer('admin.layouts.components.navigation', 'App\Http\Composers\NotificationComposer');
            View::composer('frontend.layouts.frontend', 'App\Http\Composers\FrontendFooterComposer');
            View::composer('admin.layouts.components.navigation', 'App\Http\Composers\FrontendFooterComposer');
        }
    }
}
