<?php

namespace App\Http\Middleware;

use Closure;

class FrontEnd
{
    public function handle($request, Closure $next)
    {
        if (setting('front_end_enable_disable') == 1) {
            return $next($request);
        } else {
            if (auth()->user()) {
                return redirect()->route('admin.dashboard.index');
            }
            return redirect()->route('login');
        }
    }

}
