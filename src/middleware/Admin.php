<?php namespace LeftRight\Center\Middleware;

use Auth;
use Closure;
use Redirect;
use View;

class Admin {

    public function handle($request, Closure $next)
    {
        if (Auth::guest()) {
	        return View::make('center::login.index');
        } elseif (Auth::user()->role < 2) {
	        return Redirect::to('/' . config('center.prefix'), 403);
        }
        
        return $next($request);
    }

}
