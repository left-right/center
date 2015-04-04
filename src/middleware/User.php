<?php namespace LeftRight\Center\Middleware;

use Auth;
use Closure;
use Redirect;
use LeftRight\Center\Controllers\LoginController;

class User {

    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
	        return LoginController::getIndex();
        }
        
        return $next($request);
    }

}
