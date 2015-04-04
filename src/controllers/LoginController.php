<?php namespace LeftRight\Center\Controllers;
	
use Auth;
use DateTime;
use DB;
use Hash;
use Illuminate\Support\Str;
use Mail;
use Request;
use Redirect;
use Session;
use URL;

class LoginController extends \App\Http\Controllers\Controller {

	//show login page if not logged in
	public static function getIndex() {
		
		//show install form
		try {
			if (!DB::table(config('center.db.users'))->count()) return view('center::login.install');
		} catch (\Exception $e) {
			if ($e->getCode() == 2002) {
				trigger_error('Center needs a valid database connection.');
			}
		}

		return view('center::login.index');
	}

	//handle a post to the login or install form
	public function postIndex() {
		//regular login
		if (DB::table(config('center.db.users'))->count()) {
			//attempt auth
			if (Auth::attempt(['email'=>Request::input('email'), 'password'=>Request::input('password')], true)) {

				DB::table(config('center.db.users'))->where('id', Auth::user()->id)->update([
					'last_login'=>new DateTime
				]);
				
				self::updateUserPermissions();
				
				return Redirect::intended(URL::route('home'));
			}
			return Redirect::route('home')->with('error', trans('center::site.login_invalid'));
		} 
		
		//installing, make user
		$user_id = DB::table(config('center.db.users'))->insertGetId([
			'name'			=> Request::input('name'),
			'email'			=> Request::input('email'),
			'password'		=> Hash::make(Request::input('password')),
			'last_login'	=> new DateTime,
			'updated_at'	=> new DateTime,
			'updated_by'	=> 1,
		]);

		//don't need to insert permissions; they were already inserted by refresh
		//self::setDefaultTablePermissions(config('center.db.users'));
				
		Auth::loginUsingId($user_id);

		self::updateUserPermissions();
		
		return Redirect::route('home');
	}
	
	//logout
	public function logout() {
		Auth::logout();
	    Session::forget('center.permissions');
		return Redirect::route('home');
	}

	//reset password form
	public function getReset() {
		return view('center::login.reset');
	}

	//send reset email
	public function postReset() {

		//get user
		if (!$user = DB::table(config('center.db.users'))->where('role', '<', 4)->whereNull('deleted_at')->where('email', Request::input('email'))->first()) {
			return Redirect::action('\LeftRight\Center\Controllers\LoginController@getReset')->with([
				'error'=>trans('center::users.password_reset_error')
			]);
		}

		//set new token every time
		$token = Str::random();
		DB::table(config('center.db.users'))->where('id', $user->id)->update(array('token'=>$token));

		//reset link
		$link = URL::action('\LeftRight\Center\Controllers\LoginController@getChange', array('token'=>$token, 'email'=>$user->email));

		//send reminder email
		Mail::send('center::emails.password', array('link'=>$link), function($message) use ($user)
		{
			$message->to($user->email)->subject(trans('center::users.password_reset'));
		});

		return Redirect::action('\LeftRight\Center\Controllers\LoginController@getReset')->with(array('message'=>trans('center::users.password_reset_sent')));
	}

	//reset password form
	public function getChange($email, $token) {
		//todo check email / token combo
		if (!$user = DB::table(config('center.db.users'))->whereNull('deleted_at')->where('email', $email)->where('token', $token)->first()) {
			return Redirect::action('\LeftRight\Center\Controllers\LoginController@getReset')->with(array(
				'error'=>trans('center::users.password_change_error')
			));
		}

		return view('center::login.change', array(
			'email'=>$email,
			'token'=>$token,
		));
	}

	//send reset email
	public function postChange() {
		if (!$user = DB::table(config('center.db.users'))->whereNull('deleted_at')->where('email', Request::input('email'))->where('token', Request::input('token'))->first()) {
			return Redirect::action('\LeftRight\Center\Controllers\LoginController@getReset')->with([
				'error'=>trans('center::users.password_change_error')
			]);
		}

		//successfully used reset token, time for it to die
		DB::table(config('center.db.users'))->where('id', $user->id)->update([
			'token'=>null,
			'password'=>Hash::make(Request::input('password')),
			'last_login'=>new DateTime,
		]);

		//log you in
		return Redirect::route('home');
	}
	
	//permissions, used by center controller 
	public static function permissions($user_id) {
		return DB::table(config('center.db.permissions'))->where('user', $user_id)->lists('level', 'table');
	}
	
	//check permission level for active user
	public static function checkPermission($table, $level) {
		$permissions = Session::get('center.permissions');
		if (array_key_exists($table, $permissions)) {
			if ($level == 'view') {
				return true;
			} elseif ($level == 'create') {
				if (($permissions[$table] == 'create') || ($permissions[$table] == 'edit')) return true;
			} elseif ($level == 'edit') {
				if ($permissions[$table] == 'edit') return true;
			}
		}
		return false;
	}
	
	//for dropdowns
	public static function getPermissionLevels() {
		return [
			'' => trans('center::site.permission_levels.none'),
			'view' => trans('center::site.permission_levels.view'),
			'create' => trans('center::site.permission_levels.create'),
			'edit' => trans('center::site.permission_levels.edit'),
		];
	}
	
	//set default permissions on a table (install and refresh)
	public static function setDefaultTablePermissions($table) {
		$admins = config('center.admins');
		foreach ($admins as $admin) {
			DB::table(config('center.db.permissions'))->where('user', $admin)->where('table', $table)->delete();
			DB::table(config('center.db.permissions'))->insert([
				'user' => $admin,
				'table' => $table,
				'level' => 'edit',
			]);
		}
	}

	//update user permissions
	public static function updateUserPermissions() {
	    Session::set('center.permissions', LoginController::permissions(Auth::id()));
	}

}