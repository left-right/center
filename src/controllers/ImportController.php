<?php namespace LeftRight\Center\Controllers;

use DB;
use URL;
use View;
use LeftRight\Center\Controllers\ObjectController;
use LeftRight\Center\Libraries\Table;

class ImportController extends \App\Http\Controllers\Controller {

	# Index view
	public function index() {
		if ($tables = array_diff(ObjectController::getTables(), self::getAvalonTables())) {
			foreach ($tables as &$table) $table = '\'' . $table . '\'';
			$tables = DB::select('SHOW TABLE STATUS WHERE Name IN (' . implode(',', $tables) . ')');
			foreach ($tables as &$table) {
				$table->link = URL::action('\LeftRight\Center\Controllers\ImportController@show', $table->Name);
				$table->Data_length = self::formatBytes($table->Data_length);
			}
		}
		return View::make('center::import.index', compact('tables'));
	}

	# Show view
	public function show($table) {

		$rows = DB::table($table)->get(); 
		$html = Table::rows($rows);
		$columns = DB::select('SHOW COLUMNS FROM ' . $table);
		foreach ($columns as $column) {
			if ($pos = strpos($column->Type, '(')) $column->Type = substr($column->Type, 0, $pos);
			if ($column->Type == 'int') $column->Type = 'integer';
			$html->column($column->Field, $column->Type);
		}
		$html = $html->draw($table);
		return View::make('center::import.show', compact('table', 'html'));
	}

	# Import view
	public function import($table) {
		return View::make('center::import.import', compact('table'));
	}

	# Drop
	public function drop($table) {
		return 'not yet implemented';
	}

	private static function formatBytes($size, $precision=2) {
		$base = log($size, 1024);
		$suffixes = array('', 'k', 'M', 'G', 'T');
		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}


	private static function getAvalonTables() {
		//remove the objects
		return array_merge(
			DB::table(config('center.db.objects'))->lists('name'),
			DB::table(config('center.db.fields'))->where('type', 'checkboxes')->lists('name'),
			[config('center.db.fields'), config('center.db.files'), config('center.db.object_links'), config('center.db.object_user'), config('center.db.objects'), config('center.db.users')],
			['migrations'] //laravel
		);
	}

}