<?php namespace LeftRight\Center;
	
use Illuminate\Support\ServiceProvider;
use DB;

class CenterServiceProvider extends ServiceProvider {
	
	public function register()
	{
		$this->mergeConfigFrom(__DIR__.'/config.php', 'center');
	}

	public function boot()
	{
		$this->loadViewsFrom(__DIR__.'/views', 'center');
		$this->loadTranslationsFrom(__DIR__.'/translations', 'center');
		$this->publishes([
			__DIR__.'/../assets/public' => public_path('vendor/center'),
		]);
		include __DIR__.'/routes.php';
		
		try {
			$fields  = DB::table(config('center.db.fields'))
						->join(config('center.db.objects') . ' as object', config('center.db.fields') . '.object_id', '=', 'object.id')
						->leftJoin(config('center.db.objects') . ' as related', config('center.db.fields') . '.related_object_id', '=', 'related.id')
						->select(
							config('center.db.fields') . '.object_id',
							config('center.db.fields') . '.related_object_id as related_id',
							config('center.db.fields') . '.type as type',
							config('center.db.fields') . '.name as field_name',
							'object.name as object_name', 
							'object.model as object_model', 
							'object.order_by as object_order_by',
							'object.direction as object_direction',
							'related.name as related_name', 
							'related.model as related_model', 
							'related.order_by as related_order_by',
							'related.direction as related_direction'
						)
						->orderBy('object.name')
						->get();
		} catch (\Exception $e) {
			//database not installed, would love to forward to installer, but 
			//don't want to interfere with migrations
			return false;
		}

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
					return $this->belongsTo("' . $field->related_model . '", "' . $field->field_name . '");
				}
				';

				//back from the related object
				$objects[$field->related_id]['relationships'][] = '
				public function ' . $field->object_name . '() {
					return $this->hasMany("' . $field->object_model . '", "' . $field->field_name . '");
				}
				';

			} elseif ($field->type == 'checkboxes') {

				//out from this object
				$objects[$field->object_id]['relationships'][] = '
				public function ' . $field->related_name . '() {
					return $this->belongsToMany("' . $field->related_model . '", "' . $field->field_name . '", "' . InstanceController::getKey($field->object_name) . '", "' . InstanceController::getKey($field->related_name) . '")->orderBy("' . $field->related_order_by . '", "' . $field->related_direction . '");
				}
				';
			
				//back from the related object
				$objects[$field->related_id]['relationships'][] = '
				public function ' . $field->object_name . '() {
					return $this->belongsToMany("' . $field->object_model . '", "' . $field->field_name . '", "' . InstanceController::getKey($field->related_name) . '", "' . InstanceController::getKey($field->object_name) . '")->orderBy("' . $field->object_order_by . '", "' . $field->object_direction . '");
				}
				';
			
			} elseif ($field->type == 'image') {

				$objects[$field->object_id]['relationships'][] = 'public function ' . substr($field->field_name, 0, -3) . '() {
					return $this->hasOne(\'AvalonFile\', \'id\', \'' . $field->field_name . '\');
				}';

			} elseif (in_array($field->type, array('date', 'datetime'))) {
				$objects[$field->object_id]['dates'][] = '\'' . $field->field_name . '\'';
			}
		}

		# Provide this class to extend below if needed
		eval('class AvalonFile extends Eloquent {
				protected $table = \'' . config('center.db.files') . '\';
			}');

		# Define object models (finally)
		foreach ($objects as $object) {
			eval('namespace LeftRight\Center;
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

	}
	
}