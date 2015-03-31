<?php namespace LeftRight\Center\Controllers;

use Auth;
use DB;

class TableController extends Controller {

	# Display list for home page
	public function index() {
		if (!Auth::check()) return LoginController::getIndex();

		$tables = array_where(config('center.tables'), function($key, $value) {
		    return !$value->hidden;
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
	
	# Refresh from config
	public function refresh() {
		return redirect(route('home'))->with('message', 'hi');
	}
	
}