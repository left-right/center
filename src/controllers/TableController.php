<?php namespace LeftRight\Center\Controllers;

use Auth;
use DB;
use Request;
use Schema;

class TableController extends Controller {

	# Display list for home page
	public function index() {
		if (!Auth::check()) return LoginController::getIndex();
		$tables = array_where(config('center.tables'), function($key, $value) {
		    return !$value->hidden && LoginController::checkPermission($value->name, 'view');
		});
		$objects = [];
		foreach ($tables as $table) {
			$latest = DB::table($table->name)
				->leftJoin(config('center.db.users') . ' as u2', $table->name . '.updated_by', '=', 'u2.id')
				->select('u2.name as updated_name', $table->name . '.updated_at')
				->orderBy($table->name . '.updated_at', 'desc')
				->first();
			$objects[] = (object) [
				'title' => $table->title,
				'link' => action('\LeftRight\Center\Controllers\RowController@index', $table->name),
				'updated_name' => $latest->updated_name,
				'updated_at' => $latest->updated_at,
				'count' => DB::table($table->name)->count(),
			];
		}
		return view('center::tables.index', compact('objects'));
	}
	
	# Edit table permissions page	
	public function permissions($table) {
		$table = config('center.tables.' . $table);
		$permissions = DB::table(config('center.db.permissions'))->where('table', $table->name)->lists('level', 'user');
		$users = DB::table(config('center.db.users'))->whereNull('deleted_at')->get();
		foreach ($users as &$user) {
			$user->level = (isset($permissions[$user->id])) ? $permissions[$user->id] : null;
		}
		$permission_levels = LoginController::getPermissionLevels();
		return view('center::tables.permissions', compact('users', 'table', 'permission_levels'));
	}
	
	# Handle table permissions update
	public function savePermissions($table) {
		DB::table(config('center.db.permissions'))->where('table', $table)->delete();
		foreach (Request::input('permissions') as $user=>$level) {
			if (!empty($level)) {
				DB::table(config('center.db.permissions'))->insert([
					'table' => $table,
					'user' => $user,
					'level' => $level,
				]);		
			}
		}
		LoginController::updateUserPermissions();
		return redirect(action('\LeftRight\Center\Controllers\RowController@index', $table))->with('message', trans('center::site.permissions_update_success'));
	}
}







