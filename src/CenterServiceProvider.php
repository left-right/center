<?php namespace LeftRight\Center;
	
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LeftRight\Center\Controllers\InstanceController;
use LeftRight\Center\Controllers\LoginController;
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
			if (!isset($table_properties['hidden'])) $table_properties['hidden'] = false;
			if (!isset($table_properties['order_by'])) $table_properties['order_by'] = 'id';
			$expanded_tables[$table] = (object) $table_properties;
		}
		//dd($expanded_tables);
		Config::set('center.tables', $expanded_tables);		
	}
	
	
	//loop through and process the $fields into $objects for model methods below
	private function models() {
		$objects = [];
		$tables = config('center.tables');
		foreach ($tables as $table) {
			//only make models for defined models
			if (!isset($table->model)) continue;

			$softDeletes = isset($table->fields->deleted_at);
			$relationships = $dates = [];
			
			if ($table->name == config('center.db.files')) {
				$relationships[] = 'public function url() {
					return $this->path . $this->extension;
				}';
			}
			
			if (!empty($field->related_model)) {
				if (!isset($objects[$field->related_id])) $objects[$field->related_id] = array(
					'model' => $field->related_model,
					'name' => $field->related_name,
					'dates' => array('\'created_at\'', '\'updated_at\'', '\'deleted_at\''),
					'relationships' => array(),
				);
			}
			
			foreach ($table->fields as $field) {
				
				//define relationships
				if ($field->type == 'select') {
					//this is legacy. i think we're worried about the universe folding in on itself
					if ($field->object_id == $field->related_id) continue;
	
					//out from this object
					$objects[$field->object_id]['relationships'][] = '
					public function ' . $field->related_name . '() {
						return $this->belongsTo("LeftRight\Center\Models\\' . $field->related_model . '", "' . $field->name . '");
					}
					';
	
					//back from the related object
					$objects[$field->related_id]['relationships'][] = '
					public function ' . $field->object_name . '() {
						return $this->hasMany("LeftRight\Center\Models\\' . $field->object_model . '", "' . $field->name . '");
					}
					';
	
				} elseif ($field->type == 'checkboxes') {
	
					//out from this object
					$objects[$field->object_id]['relationships'][] = '
					public function ' . $field->related_name . '() {
						return $this->belongsToMany("LeftRight\Center\Models\\' . $field->related_model . '", "' . $field->name . '", "' . InstanceController::getKey($field->object_name) . '", "' . InstanceController::getKey($field->related_name) . '")->orderBy("' . $field->related_order_by . '", "' . $field->related_direction . '");
					}
					';
				
					//back from the related object
					$objects[$field->related_id]['relationships'][] = '
					public function ' . $field->object_name . '() {
						return $this->belongsToMany("LeftRight\Center\Models\\' . $field->object_model . '", "' . $field->name . '", "' . InstanceController::getKey($field->related_name) . '", "' . InstanceController::getKey($field->object_name) . '")->orderBy("' . $field->object_order_by . '", "' . $field->object_direction . '");
					}
					';
				
				} elseif ($field->type == 'image') {
	
					$relationships[] = 'public function ' . $field->name . '() {
						return $this->hasOne(\'LeftRight\Center\File\', \'id\', \'' . $field->name . '\');
					}';
	
				} elseif (in_array($field->type, ['date', 'datetime'])) {
					$dates[] = '\'' . $field->name . '\'';
				}
			}
			
			eval('namespace LeftRight\Center\Models;
			' . ($softDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '') . '
			use Eloquent;

			class ' . $table->model . ' extends Eloquent {
			    ' . ($softDeletes ? 'use SoftDeletes;' : '') . '
				
				public $table      = \'' . $table->name . '\'; //public intentionally
				protected $guarded = array();
				protected $dates   = array(' . implode(',', $dates) . ');

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

				' . implode(' ', $relationships) . '
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