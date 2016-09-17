<?php namespace LeftRight\Center\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
use LeftRight\Center\Controllers\LoginController;
use Session;

class Permissions {

	public function handle($request, Closure $next)
	{
		if (Auth::check()) {
			if (!Session::has('center.permissions')) {
				LoginController::updateUserPermissions();
			}
		} else {
			if (Session::has('center.permissions')) {
				Session::forget('center.permissions');
			}
		}

		return $next($request);
	}

}
