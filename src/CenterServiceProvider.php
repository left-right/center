<?php namespace LeftRight\Center;
	
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LeftRight\Center\Controllers\FileController;
use LeftRight\Center\Controllers\InstanceController;
use LeftRight\Center\Controllers\LoginController;
use LeftRight\Center\Controllers\RowController;
use Auth;
use DB;
use Config;
use Session;

class CenterServiceProvider extends ServiceProvider {

	private static $field_types = [
		'address', 'checkboxes', 'checkbox', 'color', 'country', 'date', 'datetime', 'email', 'html', 
		'image', 'images', 'integer', 'latitude', 'longitude', 'money', 'password', 'permissions', 
		'phone', 'select', 'slug', 'string', 'stripe_charge', 'stripe_customer', 'text', 'time', 
		'url', 'us_state', 'user', 'zip',
	];
	
	public function register() {
		define('DOMPDF_ENABLE_PHP', true);
		$this->mergeConfigFrom(__DIR__ . '/config.defaults.php', 'center');
	    $this->app->register('Collective\Html\HtmlServiceProvider');
	    $this->app->register('Maatwebsite\Excel\ExcelServiceProvider');
	    $this->app->register('Barryvdh\DomPDF\ServiceProvider');
	    $loader = \Illuminate\Foundation\AliasLoader::getInstance();
	    $loader->alias('Form', 'Collective\Html\FormFacade');
	    $loader->alias('HTML', 'Collective\Html\HtmlFacade');
	}

	public function boot() {
		//set up publishes paths and define config locations
		$this->config();
		
		//expand table definitions
		$this->schema();

		//create model definitions on-the-fly
		$this->models();
	}

	//set up publishes paths and define config locations
	private function config() {
		$this->loadViewsFrom(__DIR__ . '/views', 'center');
		$this->loadTranslationsFrom(__DIR__ . '/translations', 'center');
		$this->publishes([
			__DIR__ . '/../assets/public' => public_path('vendor/center'),
		], 'public');
		$this->publishes([
			__DIR__ . '/config' => config_path('center'),
		], 'config');
		$this->publishes([
			__DIR__ . '/translations/en/site.php' => app_path('../resources/lang/vendor/center/en/site.php'),
			__DIR__ . '/translations/en/users.php' => app_path('../resources/lang/vendor/center/en/users.php'),
		], 'lang');
		//include __DIR__ . '/macros.php';
		include __DIR__ . '/routes.php';
	}
	
	//parse through config, expand it by applying default values and permissions
	private function schema() {
		
		//todo: consider caching this
		$expanded_tables = [];
		$tables = config('center.tables', []);

		foreach ($tables as $table=>$table_properties) {
			
			//sanitize for use in db names and php functions
			$table = str_slug($table, '_');

			//parse table definition
			$table_properties = self::associateNumericKeys($table_properties);
			$table_properties['name'] = $table;
			$table_properties['title'] = trans('center::' . $table . '.title');
			if (!isset($table_properties['keep_clean'])) $table_properties['keep_clean'] = false;
			if (!isset($table_properties['creatable'])) $table_properties['creatable'] = true;
			if (!isset($table_properties['editable'])) $table_properties['editable'] = true;
			if (!isset($table_properties['deletable'])) $table_properties['deletable'] = true;
			if (!isset($table_properties['list'])) $table_properties['list'] = [];
			if (!isset($table_properties['export'])) $table_properties['export'] = [];
			if (!isset($table_properties['search'])) $table_properties['search'] = [];
			if (!isset($table_properties['filters'])) $table_properties['filters'] = [];
			if (!isset($table_properties['timestamps'])) $table_properties['timestamps'] = true;
			
			//temp, soon to look up from permissions table
			$table_properties['dates'] = [];
			
			//loop through fields
			if (!isset($table_properties['fields'])) $table_properties['fields'] = [];
			$expanded_fields = [];
			foreach ($table_properties['fields'] as $field=>$field_properties) {
				
				//resolve shorthand, eg 'updated_at'
				if (is_int($field)) $field = $field_properties;
				if (is_string($field_properties)) {
					if (strpos($field_properties, ' ') !== false) {
						$parts = explode(' ', $field_properties);
						$field_properties = [
							'required' => in_array('required', $parts),
							'hidden' => in_array('hidden', $parts),
							'type' => implode(array_diff($parts, ['required', 'hidden'])),
						];
					} else {
						$field_properties = ['type' => $field_properties];
					}
				}
				$field_properties = self::associateNumericKeys($field_properties);

				//set types on reserved system fields
				if ($field == 'permissions') $field_properties['type'] = 'permissions';
				if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) $field_properties['type'] = 'datetime';
				if (in_array($field, ['id', 'created_by', 'updated_by', 'deleted_by', 'precedence'])) $field_properties['type'] = 'integer';
				
				//check field type is supported
				if (!in_array($field_properties['type'], self::$field_types)) {
					trigger_error('field ' . $table . '.' . $field . ' is of type ' . $field_properties['type'] . ' which is not supported.');
				}

				//set other field attributes
				$field_properties['name'] = $field;
				$field_properties['title'] = trans('center::' . $table . '.fields.' . $field);
				if (!isset($field_properties['required'])) {
					if (in_array($field, ['id', 'created_at', 'updated_at'])) {
						$field_properties['required'] = true;
					} elseif (in_array($field_properties['type'], ['checkbox'])) {
						$field_properties['required'] = true;
					} else {
						$field_properties['required'] = false;
					}
				}

				//in general, the name of a checkboxes field should be the name of its joining table
				//however your are allowed to name the source, and let the system set the joining table for you
				if (($field_properties['type'] == 'checkboxes') && empty($field_properties['source'])) {
					$field_properties['source'] = $field;
					$field = $field_properties['name'] = RowController::formatJoiningTable($table, $field);
				}
				
				if ($field_properties['type'] == 'user') $field_properties['source'] = config('center.db.users');

				//field max lengths
				if ($field_properties['type'] == 'phone') $field_properties['maxlength'] = 10;
				if ($field_properties['type'] == 'zip') $field_properties['maxlength'] = 5;
				if ($field_properties['type'] == 'us_state') $field_properties['maxlength'] = 2;
				if ($field_properties['type'] == 'country') $field_properties['maxlength'] = 2;
				
				if (!isset($field_properties['hidden'])) $field_properties['hidden'] = in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'password', 'precedence']);
				if (in_array($field_properties['type'], ['image' ,'images'])) {
					if (empty($field_properties['width'])) $field_properties['width'] = null;
					if (empty($field_properties['height'])) $field_properties['height'] = null;
				}

				//empty source on 
				if (in_array($field_properties['type'], ['latitude', 'longitude', 'slug'])) {
					if (empty($field_properties['source'])) $field_properties['source'] = null;
				}
				
				//forced overrides
				if ($field_properties['type'] == 'password') $field_properties['hidden'] = true;
				if ($field_properties['hidden']) $field_properties['required'] = false;
				
				//save
				$expanded_fields[$field] = (object) $field_properties;
				
			}
			
			//save fields to table as an object
			$table_properties['fields'] = (object) $expanded_fields;

			//default table properties
			if (!isset($table_properties['list_grouping'])) $table_properties['list_grouping'] = '';
			if (!isset($table_properties['model'])) $table_properties['model'] = studly_case(str_singular($table));
			if (!isset($table_properties['hidden'])) $table_properties['hidden'] = false;
			if (!isset($table_properties['links'])) $table_properties['links'] = [];
			if (!isset($table_properties['order_by'])) {
				$table_properties['order_by'] = [$table . '.id'=>'asc'];
			} else {
				$table_properties['order_by'] = self::associateNumericKeys($table_properties['order_by'], 'asc');
				foreach ($table_properties['order_by'] as $column => $direction) {
					//add table name to disambiguate
					if (!strstr($column, '.')) {
						unset($table_properties['order_by'][$column]);
						$table_properties['order_by'][$table . '.' . $column] = $direction;
					}
				}
			}

			//save table to $tables array as an object
			$expanded_tables[$table] = (object) $table_properties;
		}

		//sort alpha by title
		uksort($expanded_tables, function($a, $b) use ($expanded_tables) {
			return $expanded_tables[$a]->title > $expanded_tables[$b]->title;
		});

		//dd($expanded_tables);

		Config::set('center.tables', $expanded_tables);		
	}
	
	
	//loop through and process the $fields into $objects for model methods below
	private function models() {
		$relationships = $dates = [];
		$tables = config('center.tables');

		//loop through once to create relationships between tables
		foreach ($tables as $table) {

			$dates[$table->name] = [];

			if (!isset($relationships[$table->name])) $relationships[$table->name] = [];
			
			foreach ($table->fields as $field) {
				
				//define relationships
				if ($field->type == 'checkboxes') {
	
					//out from this object
					$order_by = [];
					foreach ($tables[$field->source]->order_by as $column=>$direction) {
						$order_by[] = '->orderBy("' . $column . '", "' . $direction . '")';
					}
					$relationships[$table->name][] = '
					public function ' . $tables[$field->source]->name . '() {
						return $this->belongsToMany("LeftRight\Center\Models\\' . $tables[$field->source]->model . '", "' . $field->name . '", "' . RowController::formatKeyColumn($table->name) . '", "' . RowController::formatKeyColumn($tables[$field->source]->name) . '")' . implode($order_by) . ';
					}
					';
				
					//back from the related object
					$order_by = [];
					foreach ($table->order_by as $column=>$direction) {
						$order_by[] = '->orderBy("' . $column . '", "' . $direction . '")';
					}
					$relationships[$tables[$field->source]->name][] = '
					public function ' . $table->name . '() {
						return $this->belongsToMany("LeftRight\Center\Models\\' . $table->model . '", "' . $field->name . '", "' . RowController::formatKeyColumn($tables[$field->source]->name) . '", "' . RowController::formatKeyColumn($table->name) . '")' . implode($order_by) . ';
					}
					';	

				} elseif (in_array($field->type, ['date', 'datetime'])) {

					$dates[$table->name][] = '\'' . $field->name . '\'';
				
				} elseif (($field->type == 'image') && ends_with($field->name, '_id')) {

					//cannot overwrite property
					$relationships[$table->name][] = '
					public function ' . substr($field->name, 0, -3) . '() {
						return $this->hasOne("LeftRight\Center\Models\File", "id", "' . $field->name . '");
					}';
				
				} elseif (in_array($field->type, ['select', 'user'])) {
					
					//out from this object
					$relationships[$table->name][] = '
					public function ' . $tables[$field->source]->name . '() {
						return $this->belongsTo("LeftRight\Center\Models\\' . $tables[$field->source]->model . '", "' . $field->name . '");
					}
					';
	
					//back from the related object
					$relationships[$tables[$field->source]->name][] = '
					public function ' . $table->name . '() {
						return $this->hasMany("LeftRight\Center\Models\\' . $table->model . '", "' . $field->name . '");
					}
					';
				}
			}
		}

		//now we must loop through again; the first loop set relationships on other tables
		foreach ($tables as $table) {
			eval('namespace LeftRight\Center\Models;
			use Illuminate\Database\Eloquent\SoftDeletes;
			use Auth;
			use DateTime;
			use DB;
			use Eloquent;

			class ' . $table->model . ' extends Eloquent {
			    ' . (isset($table->fields->deleted_at) ? 'use SoftDeletes;' : '') . '
				public $table      = \'' . $table->name . '\'; //public intentionally
				public $timestamps = false; //going to override if present
				protected $guarded = [];
				protected $dates   = [' . implode(',', $dates[$table->name]) . '];

				public static function boot() {
					parent::boot();
			        static::creating(function($object) {' . 
				        (isset($table->fields->precedence) ? '
						$object->precedence = DB::table(\'' . $table->name . '\')->max(\'precedence\') + 1;
						' : '') .
				        (isset($table->fields->created_by) ? '
						$object->created_by = Auth::id();
						' : '') .
				        ($table->timestamps && isset($table->fields->created_at) ? '
						$object->created_at = new DateTime();
						' : '') .
				        (isset($table->fields->updated_by) ? '
						$object->updated_by = Auth::id();
						' : '') .
				        ($table->timestamps && isset($table->fields->updated_at) ? '
						$object->updated_at = new DateTime();
						' : '') . 
					'});
			        static::updating(function($object) {' . 
			        	(isset($table->fields->updated_by) ? '
						$object->updated_by = Auth::id();
						' : '') .
				        ($table->timestamps && isset($table->fields->updated_at) ? '
						$object->updated_at = new DateTime();
						' : '') . 
			        '});
				}

				public function creator() {
					return $this->belongsTo(\'User\', \'created_by\');
				}

				public function updater() {
					return $this->belongsTo(\'User\', \'updated_by\');
				}

				' . implode(' ', $relationships[$table->name]) . '
			}');
			
		}

	}
	
	//helper for config()
	private static function associateNumericKeys($array, $default=true) {
		if (is_string($array)) {
			$array = [$array => $default];
		} else {
			foreach ($array as $key=>$value) {
				if (is_int($key)) {
					$array[$value] = $default;
					unset($array[$key]);
				}
			}
		}
		return $array;
	}
	
	//save image interface (todo move to facade)
	public static function saveImage($table_name, $field_name, $file_name, $row_id=null, $extension=null) {
		return FileController::saveImage($table_name, $field_name, $file_name, $row_id, $extension);
	}
		
}