<?php namespace LeftRight\Center\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use Redirect;
use LeftRight\Center\Controllers\LoginController;

class User {

    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
	        return LoginController::getIndex();
        }
        return $next($request);
    }

}
