<?php namespace LeftRight\Center\Controllers;
	
use Illuminate\Support\Facades\Auth;
use DateTime;
use DB;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;
use Redirect;
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
	public function postIndex(Request $request) {
		//regular login
		if (DB::table(config('center.db.users'))->count()) {
			//attempt auth
			if (Auth::attempt(['email'=>$request->input('email'), 'password'=>$request->input('password')], true)) {

				DB::table(config('center.db.users'))->where('id', Auth::user()->id)->update([
					'last_login'=>new DateTime
				]);
												
				return redirect()->intended(action('\LeftRight\Center\Controllers\TableController@index'));
				
			}
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.login_invalid'));
		} 
		
		//installing, make user
		$user_id = DB::table(config('center.db.users'))->insertGetId([
			'name'			=> $request->input('name'),
			'email'			=> $request->input('email'),
			'password'		=> Hash::make($request->input('password')),
			'last_login'	=> new DateTime,
			'updated_at'	=> new DateTime,
			'updated_by'	=> 1,
		]);
				
		Auth::loginUsingId($user_id, true);

		return redirect()->action('\LeftRight\Center\Controllers\TableController@index');
	}
	
	//logout
	public function logout(Request $request) {
		Auth::logout();
		return redirect()->action('\LeftRight\Center\Controllers\TableController@index');
	}

	//reset password form
	public function getReset() {
		return view('center::login.reset');
	}

	//send reset email
	public function postReset(Request $request) {

		//get user
		if (!$user = DB::table(config('center.db.users'))->whereNull('deleted_at')->where('email', $request->input('email'))->first()) {
			return redirect()->action('\LeftRight\Center\Controllers\LoginController@getReset')->with([
				'error'=>trans('center::site.password_reset_error')
			]);
		}

		//set new token every time
		$token = Str::random();
		DB::table(config('center.db.users'))->where('id', $user->id)->update(['token'=>$token]);

		//reset link
		$link = URL::action('\LeftRight\Center\Controllers\LoginController@getChange', ['token'=>$token, 'email'=>$user->email]);

		//send reminder email
		Mail::send('center::emails.password', ['link'=>$link], function($message) use ($user)
		{
			$message->to($user->email)->subject(trans('center::site.password_reset'));
		});

		return redirect()->action('\LeftRight\Center\Controllers\LoginController@getReset')->with(['message'=>trans('center::site.password_reset_sent')]);
	}

	//reset password form
	public function getChange($email, $token) {
		//todo check email / token combo
		if (!$user = DB::table(config('center.db.users'))->whereNull('deleted_at')->where('email', $email)->where('token', $token)->first()) {
			return redirect()->action('\LeftRight\Center\Controllers\LoginController@getReset')->with([
				'error'=>trans('center::site.password_change_error')
			]);
		}

		return view('center::login.change', [
			'email'=>$email,
			'token'=>$token,
		]);
	}

	//send reset email
	public function postChange(Request $request) {
		if (!$user = DB::table(config('center.db.users'))->whereNull('deleted_at')->where('email', $request->input('email'))->where('token', $request->input('token'))->first()) {
			return redirect()->action('\LeftRight\Center\Controllers\LoginController@getReset')->with([
				'error'=>trans('center::site.password_change_error')
			]);
		}

		//successfully used reset token, time for it to die
		DB::table(config('center.db.users'))->where('id', $user->id)->update([
			'token'=>null,
			'password'=>Hash::make($request->input('password')),
			'last_login'=>new DateTime,
		]);

		//log you in
		Auth::loginUsingId($user->id, true);
		return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('message', trans('center::site.password_change_success'));
	}
	
}