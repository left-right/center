<?php namespace LeftRight\Center\Controllers;

use Auth;

class TableController extends Controller {

	# Display list for home page
	public function index() {
		if (!Auth::check()) return LoginController::getIndex();

		$tables = array_where(config('center.tables'), function($key, $value) {
		    return $value['visibility'] != 'hidden';
		});
		$objects = [];
		foreach ($tables as $table=>$properties) {
			$objects[] = (object) [
				'title' => trans('center::tables.names.' . $table),
				'link' => action('\LeftRight\Center\Controllers\RowController@index', $table),
			];
		}
		return view('center::tables.index', compact('objects'));
	}
	
	# Refresh from config
	public function refresh() {
		return redirect(route('home'))->with('message', 'hi');
	}
	
}