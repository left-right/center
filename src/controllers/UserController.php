<?php namespace LeftRight\Center\Controllers;

class UserController extends \App\Http\Controllers\Controller {

	private static $roles = array(
		1=>'Programmer',
		2=>'Admin',
		3=>'User'
	);
	
	//show a list of users
	public function index() {
		$users = DB::table(config('center.db.users'))->whereIn('role', array_keys(self::$roles))->orderBy('name')->get();
		
		foreach ($users as &$user) {
			$user->role = self::$roles[$user->role];
			$user->link = URL::action('UserController@edit', $user->id);
			$user->delete = URL::action('UserController@delete', $user->id);
		}

		return View::make('center::users.index', array(
			'users'=>$users,
		));
	}
	
	//show the create new user form
	public function create() {
		$objects = DB::table(config('center.db.objects'))->get();
		return View::make('center::users.create', array(
			'roles'=>self::$roles,
			'objects'=>$objects,
		));
	}
	
	//save new user, email them autogenerated password
	public function store() {
		$email = Request::input('email');
		$password = Str::random(12);

		$user_id = DB::table(config('center.db.users'))->insertGetId(array(
			'name'=>Request::input('name'),
			'email'=>$email,
			'password'=>Hash::make($password),
			'role'=>Request::input('role'),
			'updated_by'=>Auth::user()->id,
			'updated_at'=>new DateTime,
		));

		self::sendWelcome($email, $password);		
		
		return Redirect::action('UserController@index')->with('user_id', $user_id);
	}

	//show edit screen
	public function edit($user_id) {
		$objects = DB::table(config('center.db.objects'))->get();
		$user = DB::table(config('center.db.users'))->where('id', $user_id)->first();
		return View::make('center::users.edit', array(
			'user'=>$user,
			'roles'=>self::$roles,
			'objects'=>$objects,
		));
	}

	//save edit to database
	public function update($user_id) {
		DB::table(config('center.db.users'))->where('id', $user_id)->update(array(
			'name'=>Request::input('name'),
			'email'=>Request::input('email'),
			'role'=>Request::input('role'),
			'updated_by'=>Auth::user()->id,
			'updated_at'=>new DateTime,
		));
		return Redirect::action('UserController@index')->with('user_id', $user_id);
	}

	//toggle active flag
	public function delete($user_id) {
		$deleted_at = (Request::input('active') == 1) ? null : new DateTime;
		DB::table(config('center.db.users'))->where('id', $user_id)->update(array(
			'updated_by'=>Auth::user()->id,
			'updated_at'=>new DateTime,
			'deleted_at'=>$deleted_at,
		));
		$updated = DB::table(config('center.db.users'))->where('id', $user_id)->pluck('updated_at');
		return Dates::relative($updated);
	}

	//destroy a never-logged-in user
	public function destroy($user_id) {
		DB::table(config('center.db.users'))->whereNull('last_login')->where('id', $user_id)->delete();
		return Redirect::action('UserController@index');
	}

	/**
	 * Re-send welcome email
	 * Have to reset user's hashed password as well
	 * Not sure this is a great idea
	 */
	public function resendWelcome($user_id) {
		$password = Str::random(12);
		$email = DB::table(config('center.db.users'))->where('id', $user_id)->pluck('email');

		DB::table(config('center.db.users'))->where('id', $user_id)->update(array(
			'password'=>Hash::make($password),
		));

		self::sendWelcome($email, $password);		
		
		return Redirect::action('UserController@index')->with('user_id', $user_id);
	}

	/**
	 * Send a welcome email to a user
	 */
	private function sendWelcome($email, $password) {
		//send notification email
		return Mail::send('center::emails.welcome', array(
			'email'=>$email,
			'password'=>$password,
			'link'=>URL::route('home'),
			), function($message) use ($email) 
		{
			$message->to($email)->subject(trans('center::messages.users_welcome_subject'));
		});		
	}
}