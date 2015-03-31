<?php namespace LeftRight\Center;
	
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LeftRight\Center\Controllers\InstanceController;
use DB;
use Config;

class CenterServiceProvider extends ServiceProvider {
	
	public function register() {
		$this->mergeConfigFrom(__DIR__.'/config.php', 'center');
	}

	public function boot() {
		$this->loadViewsFrom(__DIR__.'/views', 'center');
		$this->loadTranslationsFrom(__DIR__.'/translations', 'center');
		$this->publishes([
			__DIR__.'/../assets/public' => public_path('vendor/center'),
		], 'public');
		include __DIR__.'/macros.php';
		include __DIR__.'/routes.php';
	
		//expand table schema from config with default values
		//todo: consider caching this
		$expanded_tables = [];
		$tables = config('center.tables');
		foreach ($tables as $table=>$table_properties) {
			$table_properties = self::promoteNumericKeyToTrue($table_properties);
			$table_properties['name'] = $table;
			$table_properties['title'] = trans('center::tables.' . $table . '.title');
			$table_properties['user_can_create'] = true;
			$table_properties['user_can_edit'] = true;
			$expanded_fields = [];
			foreach ($table_properties['fields'] as $field=>$field_properties) {
				
				//resolve shorthand, eg 'updated_at'
				if (is_int($field)) $field = $field_properties;
				if (is_string($field_properties)) $field_properties = ['type'=>$field_properties];
				$field_properties = self::promoteNumericKeyToTrue($field_properties);
				
				//set types on reserved system fields
				if (in_array($field, ['created_at', 'updated_at', 'deleted_at'])) $field_properties['type'] = 'datetime';
				if (in_array($field, ['id', 'created_by', 'updated_by', 'deleted_by', 'precedence'])) $field_properties['type'] = 'int';

				//set other field attributes
				$field_properties['name'] = $field;
				$field_properties['title'] = trans('center::fields.' . $table . '.' . $field);
				if (!isset($field_properties['required'])) $field_properties['required'] = in_array($field, ['id', 'created_at', 'updated_at', 'tinyint']);
				if (!isset($field_properties['hidden'])) $field_properties['hidden'] = in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by', 'password']);
				$expanded_fields[$field] = (object) $field_properties;
			}
			$table_properties['fields'] = (object) $expanded_fields;
			if (!isset($table_properties['hidden'])) $table_properties['hidden'] = false;
			$expanded_tables[$table] = (object) $table_properties;
		}
		Config::set('center.tables', $expanded_tables);
		
		/*
		# Loop through and process the $fields into $objects for model methods below
		$objects = [];
		foreach ($fields as $field) {

			//make new empty object
			if (!isset($objects[$field->object_id])) $objects[$field->object_id] = array(
				'model' => $field->object_model,
				'name' => $field->object_name,
				'dates' => array('\'created_at\'', '\'updated_at\'', '\'deleted_at\''),
				'relationships' => array(),
			);
			if (!empty($field->related_model)) {
				if (!isset($objects[$field->related_id])) $objects[$field->related_id] = array(
					'model' => $field->related_model,
					'name' => $field->related_name,
					'dates' => array('\'created_at\'', '\'updated_at\'', '\'deleted_at\''),
					'relationships' => array(),
				);
			}

			//define relationships
			if ($field->type == 'select') {
				//this is legacy. i think we're worried about the universe folding in on itself
				if ($field->object_id == $field->related_id) continue;

				//out from this object
				$objects[$field->object_id]['relationships'][] = '
				public function ' . $field->related_name . '() {
					return $this->belongsTo("LeftRight\Center\Models\\' . $field->related_model . '", "' . $field->field_name . '");
				}
				';

				//back from the related object
				$objects[$field->related_id]['relationships'][] = '
				public function ' . $field->object_name . '() {
					return $this->hasMany("LeftRight\Center\Models\\' . $field->object_model . '", "' . $field->field_name . '");
				}
				';

			} elseif ($field->type == 'checkboxes') {

				//out from this object
				$objects[$field->object_id]['relationships'][] = '
				public function ' . $field->related_name . '() {
					return $this->belongsToMany("LeftRight\Center\Models\\' . $field->related_model . '", "' . $field->field_name . '", "' . InstanceController::getKey($field->object_name) . '", "' . InstanceController::getKey($field->related_name) . '")->orderBy("' . $field->related_order_by . '", "' . $field->related_direction . '");
				}
				';
			
				//back from the related object
				$objects[$field->related_id]['relationships'][] = '
				public function ' . $field->object_name . '() {
					return $this->belongsToMany("LeftRight\Center\Models\\' . $field->object_model . '", "' . $field->field_name . '", "' . InstanceController::getKey($field->related_name) . '", "' . InstanceController::getKey($field->object_name) . '")->orderBy("' . $field->object_order_by . '", "' . $field->object_direction . '");
				}
				';
			
			} elseif ($field->type == 'image') {

				$objects[$field->object_id]['relationships'][] = 'public function ' . substr($field->field_name, 0, -3) . '() {
					return $this->hasOne(\'LeftRight\Center\File\', \'id\', \'' . $field->field_name . '\');
				}';

			} elseif (in_array($field->type, array('date', 'datetime'))) {
				$objects[$field->object_id]['dates'][] = '\'' . $field->field_name . '\'';
			}
		}

		# Provide this class to extend below if needed
		eval('namespace LeftRight\Center;
			use Eloquent;
			class File extends Eloquent {
				protected $table = \'' . config('center.db.files') . '\';
			}');

		# Define object models (finally)
		foreach ($objects as $object) {
			
			eval('namespace LeftRight\Center\Models;
			use Illuminate\Database\Eloquent\SoftDeletes;
			use Eloquent;

			class ' . $object['model'] . ' extends Eloquent {
			    use SoftDeletes;
				
				public $table      = \'' . $object['name'] . '\'; //public intentionally
				protected $guarded = array();
				protected $dates   = array(' . implode(',', $object['dates']) . ');

				public static function boot() {
					parent::boot();
			        static::creating(function($object) {
						$object->precedence = DB::table(\'' . $object['name'] . '\')->max(\'precedence\') + 1;
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

				' . implode(' ', $object['relationships']) . '
			}');
		}
		*/
	}
	
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