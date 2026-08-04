<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use App\Models\Addon;
use Illuminate\Support\Facades\File;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()->id ?: $request->ip());
        });
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));

        if (file_exists(storage_path('installed'))) {
            $addons = Addon::all();
            if (!blank($addons)) {
                foreach ($addons as $addon) {
                    if (isset(json_decode($addon->files)->web_route)) {
                        if (File::exists(__DIR__ . "/../../routes/{$addon->slug}.php")) {
                            Route::middleware('web')
                                ->namespace($this->namespace)
                                ->group(__DIR__ . "/../../routes/{$addon->slug}.php");
                        }
                    }
                }
            }
        }
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));
            
        if (file_exists(storage_path('installed'))) {
            $addons = Addon::all();
            if (!blank($addons)) {
                foreach ($addons as $addon) {
                    if (isset(json_decode($addon->files)->api_route)) {
                        if (File::exists(__DIR__ . "/../../routes/{$addon->slug}.php")) {
                            Route::prefix('api')
                                ->middleware('api')
                                ->namespace($this->namespace)
                                ->group(__DIR__ . "/../../routes/{$addon->slug}-api.php");
                        }
                    }
                }
            }
        }
    }
}
