<?php namespace LeftRight\Center;
	
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LeftRight\Center\Controllers\InstanceController;
use LeftRight\Center\Controllers\LoginController;
use LeftRight\Center\Controllers\RowController;
use Auth;
use DB;
use Config;
use Session;

class CenterServiceProvider extends ServiceProvider {

	private static $field_types = [
		'checkboxes', 'checkbox', 'color', 'date', 'datetime', 'email', 'html', 
		'image', 'images', 'integer', 'money', 'password', 'permissions', 'select', 
		'slug', 'string', 'text', 'time', 'url', 'us_state', 'user', 'zip',
	];
	
	public function register() {
		$this->mergeConfigFrom(__DIR__.'/config.php', 'center');
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
			__DIR__ . '/config.sample.php' => config_path('center.php'),
		], 'config');
		$this->publishes([
			__DIR__ . '/translations/en/site.php' => app_path('../resources/lang/packages/en/center/site.php'),
			__DIR__ . '/translations/en/users.php' => app_path('../resources/lang/packages/en/center/users.php'),
		], 'lang');
		include __DIR__ . '/macros.php';
		include __DIR__ . '/routes.php';
	}
	
	//parse through config, expand it by applying default values and permissions
	private function schema() {
		
		//todo: consider caching this
		$expanded_tables = [];
		$tables = array_merge(config('center.tables', []), config('center.system_tables'));
		foreach ($tables as $table=>$table_properties) {
			
			$table = str_slug($table, '_');

			//parse table definition
			$table_properties = self::promoteNumericKeyToTrue($table_properties);
			$table_properties['name'] = $table;
			$table_properties['title'] = trans('center::' . $table . '.title');
			if (!isset($table_properties['keep_clean'])) $table_properties['keep_clean'] = false;
			if (!isset($table_properties['search'])) $table_properties['search'] = false;
			if (!isset($table_properties['list'])) $table_properties['list'] = [];
			
			//temp, soon to look up from permissions table
			$table_properties['dates'] = [];
			
			//loop through fields
			if (!isset($table_properties['fields'])) $table_properties['fields'] = [];
			$expanded_fields = [];
			foreach ($table_properties['fields'] as $field=>$field_properties) {
				
				//resolve shorthand, eg 'updated_at'
				if (is_int($field)) $field = $field_properties;
				if (is_string($field_properties)) $field_properties = ['type'=>$field_properties];
				$field_properties = self::promoteNumericKeyToTrue($field_properties);

				//set types on reserved system fields
				if ($field == 'permissions') $field_properties['type'] = 'permissions';
				if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) $field_properties['type'] = 'datetime';
				if (in_array($field, ['id', 'created_by', 'updated_by', 'deleted_by', 'precedence'])) $field_properties['type'] = 'integer';
				
				//check
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

				//in general, the name of a checkbox field should be the name of its joining table
				//however your are allowed to name the source, and let center set the joining table for you
				if (($field_properties['type'] == 'checkboxes') && empty($field_properties['source'])) {
					$field_properties['source'] = $field;
					$field = $field_properties['name'] = RowController::formatJoiningTable($table, $field);
				}
				if (!isset($field_properties['hidden'])) $field_properties['hidden'] = in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'password', 'precedence']);
				if (in_array($field_properties['type'], ['image' ,'images'])) {
					if (empty($field_properties['width'])) $field_properties['width'] = null;
					if (empty($field_properties['height'])) $field_properties['height'] = null;
				}
				
				//forced overrides
				if ($field_properties['type'] == 'password') $field_properties['hidden'] = true;
				if ($field_properties['hidden']) $field_properties['required'] = false;
				
				//save
				$expanded_fields[$field] = (object) $field_properties;
				
			}
			
			//forced overrides
			if (($table == config('center.db.users')) && !isset($table_properties['fields']['deleted_at'])) {
				//users are not deletable, could throw off relationships
				$table_properties['fields']['deleted_at'] = [
					'name' => 'deleted_at',
					'type' => 'datetime',
					'hidden' => true,
					'required' => false,
				];
			}
			
			$table_properties['fields'] = (object) $expanded_fields;
			if (!isset($table_properties['model'])) $table_properties['model'] = studly_case(str_singular($table));
			if (!isset($table_properties['create'])) $table_properties['create'] = true;
			if (!isset($table_properties['hidden'])) $table_properties['hidden'] = false;
			if (!isset($table_properties['order_by'])) $table_properties['order_by'] = 'id';
			if (!isset($table_properties['direction'])) $table_properties['direction'] = 'ASC';
			$expanded_tables[$table] = (object) $table_properties;
		}
		//dd($expanded_tables);
		Config::set('center.tables', $expanded_tables);		
	}
	
	
	//loop through and process the $fields into $objects for model methods below
	private function models() {
		$relationships = [];
		$tables = config('center.tables');

		//loop through once to create relationships between tables
		foreach ($tables as $table) {

			$softDeletes = isset($table->fields->deleted_at);
			
			$dates = [];

			if (!isset($relationships[$table->name])) $relationships[$table->name] = [];
			
			foreach ($table->fields as $field) {
				
				//define relationships
				if ($field->type == 'checkboxes') {
	
					//out from this object
					$relationships[$table->name][] = '
					public function ' . $tables[$field->source]->name . '() {
						return $this->belongsToMany("LeftRight\Center\Models\\' . $tables[$field->source]->model . '", "' . $field->name . '", "' . RowController::formatKeyColumn($table->name) . '", "' . RowController::formatKeyColumn($tables[$field->source]->name) . '")->orderBy("' . $tables[$field->source]->order_by . '", "' . $tables[$field->source]->direction . '");
					}
					';
				
					//back from the related object
					$relationships[$tables[$field->source]->name][] = '
					public function ' . $table->name . '() {
						return $this->belongsToMany("LeftRight\Center\Models\\' . $table->model . '", "' . $field->name . '", "' . RowController::formatKeyColumn($tables[$field->source]->name) . '", "' . RowController::formatKeyColumn($table->name) . '")->orderBy("' . $table->order_by . '", "' . $table->direction . '");
					}
					';	

				} elseif (in_array($field->type, ['date', 'datetime'])) {
					
					$dates[] = '\'' . $field->name . '\'';
				
				} elseif (($field->type == 'image') && ends_with($field->name, '_id')) {
				
					//cannot overwrite property
					$relationships[$table->name][] = '
					public function ' . substr($field->name, 0, -3) . '() {
						return $this->hasOne("LeftRight\Center\Models\File", "id", "' . $field->name . '");
					}';
				
				} elseif ($field->type == 'select') {
					
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
			' . ($softDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '') . '
			use Eloquent;

			class ' . $table->model . ' extends Eloquent {
			    ' . ($softDeletes ? 'use SoftDeletes;' : '') . '
				
				public $table      = \'' . $table->name . '\'; //public intentionally
				protected $guarded = [];
				protected $dates   = [' . implode(',', $dates) . '];

				public static function boot() {
					parent::boot();
			        static::creating(function($object) {
						$object->precedence = DB::table(\'' . $table->name . '\')->max(\'precedence\') + 1;
						$object->created_by = Auth::id();
						$object->updated_by = Auth::id();
			        });
			        static::updating(function($object) {
						$object->updated_by = Auth::id();
			        });
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
	private static function promoteNumericKeyToTrue($array) {
		foreach ($array as $key=>$value) {
			if (is_int($key)) {
				$array[$value] = true;
				unset($array[$key]);
			}
		}
		return $array;
	}
	
}