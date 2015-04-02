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
	
	# Refresh from config
	public function refresh() {
	
		$tables = config('center.tables');
		//dd($tables);
		
		foreach ($tables as $table) {
			
			//create if doesn't exist, every table gets an id
			if (!Schema::hasTable($table->name)) {
				Schema::create($table->name, function($t) {
				    $t->increments('id');
				});
			}
			
			foreach ($table->fields as $field) {
				Schema::table($table->name, function($t) use($table, $field) {
					
					//set type
					switch ($field->type) {
						case 'checkbox':
							eval('$t->boolean($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'color':
						case 'email':
						case 'password':
						case 'slug':
						case 'string':
						case 'url':
						case 'us_state':
						case 'zip':
							if (!isset($field->maxlength)) $field->maxlength = 255;
							eval('$t->string($field->name, $field->maxlength)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'date':
							eval('$t->date($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'datetime':
							eval('$t->datetime($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'html':
						case 'text':
							eval('$t->text($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'image':
						case 'integer':
						case 'select':
						case 'user':
							eval('$t->integer($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'money':
							eval('$t->decimal($field->name, 5, 2)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'permissions':
							break;

					    default:
							trigger_error($field->type . ' not supported yet!');
					}

					//remove unused columns?
					if ($table->keep_clean) {
						$columns = Schema::getColumnListing($table->name);
						$fields = array_keys((array) $table->fields);
						$columns = array_diff($columns, $fields, ['id']);
						foreach ($columns as $column) $t->dropColumn($column);
					}

				});
			}
			
		}
		
		/*removed unused tables?
		if (config('center.keep_clean')) {
			$tables = Schema::getTableListing();
			dd($tables);
		}*/
		
		return redirect(route('home'))->with('message', trans('center::site.refresh_success'));
	}
	
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







