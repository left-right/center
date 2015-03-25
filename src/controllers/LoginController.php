<?php namespace LeftRight\Center\Controllers;
	
use Auth;
use DateTime;
use DB;
use Hash;
use Illuminate\Support\Str;
use Mail;
use Request;
use Redirect;
use URL;

class LoginController extends \App\Http\Controllers\Controller {

	//show login page if not logged in
	public static function getIndex() {
		
		if (Auth::check()) return ObjectController::index;
		
		//show install form
		if (!DB::table(config('center.db.users'))->count()) return view('center::login.install');

		return view('center::login.index');
	}

	//handle a post to the login or install form
	public function postIndex() {
		//regular login
		die('hi');
		if (DB::table(config('center.db.users'))->count()) {
			//attempt auth
			if (Auth::attempt(['email'=>Request::input('email'), 'password'=>Request::input('password')], true)) {

				DB::table(config('center.db.users'))->where('id', Auth::user()->id)->update([
					'last_login'=>new DateTime
				]);
				die('valid');
				return Redirect::intended(URL::route('home'));
			}
			die('invalid');
			return Redirect::route('home')->with('error', trans('center::site.login_invalid'));
		} 
		
		//make user
		$user_id = DB::table(config('center.db.users'))->insertGetId(array(
			'name'			=> Request::input('name'),
			'email'			=> Request::input('email'),
			'password'		=> Hash::make(Request::input('password')),
			'role'			=> 1,
			'last_login'	=> new DateTime,
			'created_at'	=> new DateTime,
			'updated_at'	=> new DateTime,
		));
		
		//show that user created self
		DB::table(config('center.db.users'))->where('id', $user_id)->update(array('updated_by'=>$user_id));
		
		Auth::loginUsingId($user_id);
		
		return Redirect::route('home');
	}
	
	//logout
	public function getLogout() {
		Auth::logout();
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

}