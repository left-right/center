<?php namespace LeftRight\Center\Middleware;

use Auth;
use Closure;
use Redirect;
use View;

class User {

    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
	        return View::make('center::login.index');
        } elseif (Auth::user()->role === null) {
	        return Redirect::to('/', 403);
        }
        
        return $next($request);
    }

}
