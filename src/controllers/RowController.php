<?php namespace LeftRight\Center\Controllers;

use App;
use Aws\Common\Enum\Region;
use Aws\Laravel\AwsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\ExcelServiceProvider;
use DateTime;
use DB;
use LeftRight\Center\Libraries\Slug;
use Redirect;
use Request;
use URL;
use Validator;

class RowController extends \App\Http\Controllers\Controller {

	# Show list of instances for an object
	# $group_by_id is for when coming from a linked object
	public function index($table, $linked_id=false) {

		# Get info about the object
		$table = config('center.tables.' . $table);
		//dd($table);

		# Start query
		$rows = DB::table($table->name);

		# Empty arrays mainly for search
		$select_fields = $date_fields = $columns = [];
		$date_fields = ['created_at'=>'Created', 'updated_at'=>'Updated'];

		# Build select statement
		$rows->select([$table->name . '.id', $table->name . '.updated_at', $table->name . '.deleted_at']);
		foreach ($table->list as $field) {
			$field = $table->fields->{$field};
				
			if ($field->type == 'checkboxes') {
				$related_object = self::getRelatedObject($field->related_object_id);
				$rows->addSelect(DB::raw('(SELECT GROUP_CONCAT(' . $related_object->name . '.' . $related_object->field->name . ' SEPARATOR ", ") 
					FROM ' . $related_object->name . ' 
					JOIN ' . $field->name . ' ON ' . $related_object->name . '.id = ' . $field->name . '.' . self::getKey($related_object->name) . '
					WHERE ' . $field->name . '.' . self::getKey($table->name) . ' = ' . $table->name . '.id 
					ORDER BY ' . $related_object->name . '.' . $related_object->field->name . ') AS ' . $field->name));
			} elseif ($field->type == 'image') {
				$rows
					->leftJoin(config('center.db.files'), $table->name . '.' . $field->name, '=', config('center.db.files') . '.id')
					->addSelect(config('center.db.files') . '.url AS ' . $field->name . '_url');
			} elseif ($field->type == 'select') {
				$related_object = self::getRelatedObject($field->related_object_id);
				$rows
					->leftJoin($related_object->name, $table->name . '.' . $field->name, '=', $related_object->name . '.id')
					->addSelect($related_object->name . '.' . $related_object->field->name . ' AS ' . $field->name);
				$text_fields[] = $related_object->name . '.' . $related_object->field->name;
			} elseif ($field->type == 'user') {
				$rows
					->leftJoin(config('center.db.users'), $table->name . '.' . $field->name, '=', config('center.db.users') . '.id')
					->addSelect(config('center.db.users') . '.name AS ' . $field->name);
			} else {
				//normal, selectable field
				$rows->addSelect($table->name . '.' . $field->name);
			}
			
			//add to table columns
			$columns[] = $field;

			//search
			if (in_array($field->type, ['string', 'text', 'html'])) {
				$text_fields[] = $table->name . '.' . $field->name;
			} elseif (in_array($field->type, ['select'])) {
				$select_fields[] = $field;
			} elseif (in_array($field->type, ['date', 'datetime'])) {
				
			}
		}

		# Handle group-by fields
		$table->nested = false;
		if (!empty($table->group_by_field)) {
			$grouped_field = DB::table(config('center.db.fields'))->where('id', $object->group_by_field)->first();
			$grouped_object = self::getRelatedObject($grouped_field->related_object_id);
			if ($grouped_object->id == $object->id) {
				//nested object
				$object->nested = true;
			} else {
				# Include group_by_field in resultset
				$rows
					->orderBy($grouped_object->name . '.' . $grouped_object->order_by, $grouped_object->direction)
					->addSelect($grouped_object->name . '.' . $grouped_object->field->name . ' as group');
	
				# If $linked_id, limit scope to just $linked_id
				if ($linked_id) {
					$rows->where($grouped_field->name, $linked_id);
				}
			}
		}

		# Set the order and direction
		$rows->orderBy($table->name . '.' . $table->order_by);

		$searching = false;

		# Text search?
		if ($table->search && Request::has('search')) {
			$searching = true;
			foreach ($table->search as $field) {
				$rows->orWhere($field, 'LIKE', '%' . Request::input('search') . '%');
			}
		}
		
		# Filter search?
		foreach ($select_fields as $select) {
			if (Request::has($select->name)) {
				$searching = true;
				$rows->where($select->name, Request::input($select->name));
			}
		}

		# Run query and save it to a variable
		$rows = $rows->get();

		# Set URLs on each instance
		if ($table->user_can_edit) {
			foreach ($rows as &$instance) {
				$instance->link = action('\LeftRight\Center\Controllers\RowController@edit', [$table->name, $instance->id, $linked_id]);
				$instance->delete = action('\LeftRight\Center\Controllers\RowController@delete', [$table->name, $instance->id]);
			}
		}

		# If it's a nested object, nest-ify the resultset
		if ($table->nested) {
			$list = array();
			foreach ($rows as &$instance) {
				$instance->children = array();
				if (empty($instance->{$grouped_field->name})) { //$grouped_field->name is for ex parent_id
					$list[] = $instance;
				} elseif (self::nestedNodeExists($list, $instance->{$grouped_field->name}, $instance)) {
					//attached child to parent node
				} else {
					//an error occurred; a parent should exist but is not yet present
				}
			}
			$rows = $list;
		}

		# Search filters for the sidebar
		$filters = [];
		foreach ($select_fields as $select) {
			$related_object = self::getRelatedObject($select->related_object_id);
			$options = DB::table($related_object->name)->orderBy($related_object->order_by, $related_object->direction)->lists($related_object->field->name, 'id');
			$filters[$select->name] = [''=>$select->title] + $options;
		}
		
		$return = compact('table', 'columns', 'rows', 'filters', 'searching');

		# Return array to edit()
		if ($linked_id) {
			$object->group_by_field = false; //hacky, but easiest way to remove grouping
			return $return;
		}

		# Return HTML view
		return view('center::rows.index', $return);
	}

	//show create form for an object instance
	public function create($table, $linked_id=false) {
		$table = config('center.tables.' . $table);
		$options = [];
		
		# Add return var to the queue
		if ($linked_id) {
			$return_to = action('\LeftRight\Center\Controllers\InstanceController@edit', [self::getRelatedObjectName($object), $linked_id]);
		} elseif (URL::previous()) {
			$return_to = URL::previous();
		} else {
			$return_to = action('\LeftRight\Center\Controllers\InstanceController@index', $table->name);
		}

		foreach ($table->fields as $field) {
			if (($field->type == 'checkboxes') || ($field->type == 'select')) {

				//load options for checkboxes or selects
				$related_object = self::getRelatedObject($field->related_object_id);
				$field->options = DB::table($related_object->name)->orderBy($related_object->order_by, $related_object->direction)->lists($related_object->field->name, 'id');

				//indent nested selects
				if ($field->type == 'select' && !empty($related_object->group_by_field)) {
					$grouped_field = DB::table(config('center.db.fields'))->where('id', $related_object->group_by_field)->first();
					if ($grouped_field->object_id == $grouped_field->related_object_id) {
						$field->options = $parents = array();
						$options = DB::table($related_object->name)->orderBy($related_object->order_by, $related_object->direction)->get();
						foreach ($options as $option) {
							if (!empty($option->{$grouped_field->name})) {
								//calculate indent
								if (in_array($option->{$grouped_field->name}, $parents)) {
									$parents = array_slice($parents, 0, array_search($option->{$grouped_field->name}, $parents) + 1);
								} else {
									$parents[] = $option->{$grouped_field->name};
								}
								$option->{$related_object->field->name} = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', count($parents)) . $option->{$related_object->field->name};
							} elseif (count($parents)) {
								$parents = array();
							}
							$field->options[$option->id] = $option->{$related_object->field->name};
						}
					}
				}

				//select might be nullable
				if ($field->type == 'select' && !$field->required) {
					$field->options = [''=>''] + $field->options;
				}
			} elseif ($field->type == 'user') {
				$field->options = DB::table(config('center.db.users'))->orderBy('name')->lists('name', 'id');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif ($field->type == 'us_state') {
				$field->options = FieldController::usStates();
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif (in_array($field->type, array('image', 'images'))) {
				list($field->screen_width, $field->screen_height) = FileController::getImageDimensions($field->width, $field->height);
			}
		}

		return view('center::rows.create', compact('table', 'linked_id', 'return_to'));
	}

	//save a new object instance to the database
	public function store($table, $linked_id=false) {
		$table = config('center.tables.' . $table);
		
		//metadata
		$inserts = [];

		//run various cleanup processes on the fields
		foreach ($table->fields as $field) {
			if (Request::has($field->name)) {
				$inserts[$field->name] = self::sanitize($field);
			} elseif ($field->type == 'checkbox') {
				$inserts[$field->name] = 0;
			}
			if ($field->name == 'created_at') $inserts['created_at'] = new DateTime;
			if ($field->name == 'updated_at') $inserts['updated_at'] = new DateTime;
			if ($field->name == 'created_by') $inserts['updated_at'] = Auth::user()->id;
			if ($field->name == 'updated_by') $inserts['updated_at'] = Auth::user()->id;
			if ($field->name == 'precedence') $inserts['updated_at'] = DB::table($table->name)->max('precedence') + 1;
		}

		//validate
		$v = Validator::make(Request::all(), [
		    'email' => 'required|unique:users|max:255',
		]);
		
		if ($v->fails()) {
		    return redirect()->back()->withInput()->withErrors($v->errors());
		}

		//slug
		if (property_exists($table->fields, 'slug')) {
			//determine where slug is coming from
			if ($slug_source = Slug::source($object->id)) {
				$slug_source = Request::input($slug_source);
			} else {
				$slug_source = date('Y-m-d');
			}
	
			//get other values to check uniqueness
			$uniques = DB::table($table->name)->lists('slug');
	
			//add unique, formatted slug to the insert batch
			$inserts['slug'] = Slug::make($slug_source, $uniques);
		}

		//run insert
		$row_id = DB::table($table->name)->insertGetId($inserts);
		
		//handle any checkboxes, had to wait for instance_id
		foreach ($table->fields as $field) {
			if ($field->type == 'checkboxes') {
				//figure out schema, loop through and save all the checkboxes
				$object_column = self::getKey($table->name);
				$remote_column = self::getKey($field->related_object_id);
				if (Request::has($field->name)) {
					foreach (Request::input($field->name) as $related_id) {
						DB::table($field->name)->insert(array(
							$object_column=>$row_id,
							$remote_column=>$related_id,
						));
					}
				}
			} elseif ($field->type == 'image') {
				DB::table(config('center.db.files'))->where('id', Request::input($field->name))->update(['instance_id'=>$row_id]);
			} elseif ($field->type == 'images') {
				$file_ids = explode(',', Request::input($field->name));
				$precedence = 0;
				foreach ($file_ids as $file_id) {
					DB::table(config('center.db.files'))->where('id', $file_id)->update([
						'instance_id'=>$row_id,
						'precedence'=>++$precedence,
					]);
				}
			}
		}

		/*update objects table with latest counts
		DB::table(config('center.db.objects'))->where('id', $object->id)->update([
			'count'=>DB::table($table->name)->whereNull('deleted_at')->count(),
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id
		]);*/

		//clean up any abandoned files
		//FileController::cleanup();

		//return to target		
		return Redirect::to(Request::input('return_to'));
	}
	
	//show edit form
	public function edit($table, $row_id, $linked_id=false) {
	
		# Get object / field / whatever infoz
		$table = config('center.tables.' . $table);
		$instance = DB::table($table->name)->where('id', $row_id)->first();

		# Add return var to the queue
		if ($linked_id) {
			$return_to = action('\LeftRight\Center\Controllers\InstanceController@edit', [self::getRelatedObjectName($object), $linked_id]);
		} elseif (URL::previous()) {
			$return_to = URL::previous();
		} else {
			$return_to = action('\LeftRight\Center\Controllers\InstanceController@index', $table->name);
		}

		//format instance values for form
		foreach ($table->fields as $field) {
			if ($field->type == 'datetime') {
				if (!empty($instance->{$field->name})) $instance->{$field->name} = date('m/d/Y h:i A', strtotime($instance->{$field->name}));
			} elseif (($field->type == 'checkboxes') || ($field->type == 'select')) {

				//load options for checkboxes or selects
				$related_object = self::getRelatedObject($field->related_object_id);
				$field->options = DB::table($related_object->name)->orderBy($related_object->order_by, $related_object->direction)->lists($related_object->field->name, 'id');

				//indent nested selects
				if ($field->type == 'select' && !empty($related_object->group_by_field)) {
					$grouped_field = DB::table(config('center.db.fields'))->where('id', $related_object->group_by_field)->first();
					if ($grouped_field->object_id == $grouped_field->related_object_id) {
						$field->options = $parents = array();
						$options = DB::table($related_object->name)->orderBy($related_object->order_by, $related_object->direction)->get();
						foreach ($options as $option) {
							if (!empty($option->{$grouped_field->name})) {
								//calculate indent
								if (in_array($option->{$grouped_field->name}, $parents)) {
									$parents = array_slice($parents, 0, array_search($option->{$grouped_field->name}, $parents) + 1);
								} else {
									$parents[] = $option->{$grouped_field->name};
								}
								$option->{$related_object->field->name} = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', count($parents)) . $option->{$related_object->field->name};
							} elseif (count($parents)) {
								$parents = array();
							}
							$field->options[$option->id] = $option->{$related_object->field->name};
						}
					}
				}

				//select might be nullable
				if ($field->type == 'select' && !$field->required) {
					$field->options = [''=>''] + $field->options;
				}

				//get checkbox values todo make a function for consistently getting these checkbox column names
				if ($field->type == 'checkboxes') {
					$table_key = Str::singular($table->name) . '_id';
					$foreign_key = Str::singular($related_object->name) . '_id';
					$instance->{$field->name} = DB::table($field->name)->where($table_key, $instance->id)->lists($foreign_key);
				}
			} elseif ($field->type == 'image') {
				$instance->{$field->name} = DB::table(config('center.db.files'))->where('id', $instance->{$field->name})->first();
				if (!empty($instance->{$field->name}->width) && !empty($instance->{$field->name}->height)) {
					$field->width = $instance->{$field->name}->width;
					$field->height = $instance->{$field->name}->height;
				}
				list($field->screen_width, $field->screen_height) = FileController::getImageDimensions($field->width, $field->height);
			} elseif ($field->type == 'images') {
				$instance->{$field->name} = DB::table(config('center.db.files'))->where('field_id', $field->id)->where('instance_id', $instance->id)->orderBy('precedence', 'asc')->get();
				foreach ($instance->{$field->name} as &$image) {
					if (!empty($image->width) && !empty($image->height)) {
						$image->screen_width = $image->width;
						$image->screen_width = $image->height;
					}
				}
				list($field->screen_width, $field->screen_height) = FileController::getImageDimensions($field->width, $field->height);
			} elseif ($field->type == 'slug') {
				if ($field->required && empty($instance->{$field->name}) && $field->related_field_id) {
					//slugify related field to populate this one
					foreach ($fields as $related_field) {
						if ($related_field->id == $field->related_field_id) {
							$instance->{$field->name} = Str::slug($instance->{$related_field->name});
						}
					}
				}
			} elseif ($field->type == 'user') {
				$field->options = DB::table(config('center.db.users'))->orderBy('name')->lists('name', 'id');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif ($field->type == 'us_state') {
				$field->options = FieldController::usStates();
				if (!$field->required) $field->options = [''=>''] + $field->options;
			}
		}

		$links = [];
		/* Get linked objects
		$links = DB::table(config('center.db.object_links'))
				->where('object_id', $table->id)
				->join(config('center.db.objects'), config('center.db.object_links') . '.linked_id', '=', config('center.db.objects') . '.id')
				->lists(config('center.db.objects') . '.name');
		foreach ($links as &$link) {
			$link = self::index($link, $row_id, $linked_id);
		}*/

		return view('center::rows.edit', compact('table', 'instance', 'links', 'linked_id', 'return_to'));
	}
	
	//save edits to database
	public function update($table, $row_id, $linked_id=false) {
		$table = config('center.tables.' . $table);
		
		//metadata
		$updates = [
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id,
		];
		
		//run loop through the fields
		foreach ($table->fields as $field) {
			if ($field->hidden) continue;
			if ($field->type == 'checkboxes') {
				
				# Figure out schema
				$object_column = self::getKey($table->name);
				$remote_column = self::getKey($field->related_object_id);

				# Clear old values
				DB::table($field->name)->where($object_column, $row_id)->delete();

				# Loop through and save all the checkboxes
				if (Request::has($field->name)) {
					foreach (Request::input($field->name) as $related_id) {
						DB::table($field->name)->insert(array(
							$object_column=>$row_id,
							$remote_column=>$related_id,
						));
					}
				}
			} elseif ($field->type == 'images') {

				# Unset any old file associations (will get cleaned up after this loop)
				DB::table(config('center.db.files'))
					->where('field_id', $field->id)
					->where('instance_id', $row_id)
					->update(array('instance_id'=>null));

				# Create new associations
				$file_ids = explode(',', Request::input($field->name));
				$precedence = 0;
				foreach ($file_ids as $file_id) {
					DB::table(config('center.db.files'))
						->where('id', $file_id)
						->update(array(
							'instance_id'=>$row_id,
							'precedence'=>++$precedence,
						));
				}

			} else {
				if ($field->type == 'image') {

					# Unset any old file associations (will get cleaned up after this loop)
					DB::table(config('center.db.files'))
						->where('table', $table->name)
						->where('field', $field->name)
						->where('row_id', $row_id)
						->update(['row_id'=>null]);


					# Capture the uploaded file by setting the reverse-lookup
					DB::table(config('center.db.files'))
						->where('id', Request::input($field->name))
						->update(['row_id'=>$row_id]);

				}

				$updates[$field->name] = self::sanitize($field);
			}
		}

		//slug
		/*
		if (!empty($object->url)) {
			$uniques = DB::table($table->name)->where('id', '<>', $row_id)->lists('slug');
			$updates['slug'] = Slug::make(Request::input('slug'), $uniques);
		}
		*/
		
		/* //todo manage a redirect table if client demand warrants it
		$old_slug = DB::table($table->name)->find($row_id)->pluck('slug');
		if ($updates['slug'] != $old_slug) {
		}*/
				
		//run update
		DB::table($table->name)->where('id', $row_id)->update($updates);
		
		//clean up abandoned files
		//FileController::cleanup();

		/*update object meta
		DB::table(config('center.db.objects'))->where('id', $object->id)->update([
			'count'=>DB::table($table->name)->whereNull('deleted_at')->count(),
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id
		]);*/
		
		return Redirect::to(Request::input('return_to'));
	}
	
	# Remove object from db - todo check key/constraints
	public function destroy($table, $row_id) {
		$table = config('center.tables.' . $table);
		DB::table($table->name)->where('id', $row_id)->delete();

		return Redirect::to(Request::input('return_to'));
	}
	
	# Reorder fields by drag-and-drop
	public function reorder($table) {
		$object = DB::table(config('center.db.objects'))->where('name', $table)->first();

		//determine whether nested
		$object->nested = false;
		if (!empty($object->group_by_field)) {
			$grouped_field = DB::table(config('center.db.fields'))->where('id', $object->group_by_field)->first();
			if ($grouped_field->related_object_id == $object->id) {
				$object->nested = true;
			}
		}

		if ($object->nested) {
			$row_ids = explode(',', Request::input('list'));
			$precedence = 1;
			foreach ($row_ids as $row_id) {
				if (!empty($row_id)) {
					DB::table($table->name)->where('id', $row_id)->update(['precedence'=>$precedence++]);
				}
			}
			if (Request::has('id') && Request::has('parent_id')) {
				DB::table($table->name)->where('id', Request::input('id'))->update([
					'parent_id'=>Request::input('parent_id'),
					//updated_at, updated_by?
				]);
			}
			return 'done reordering nested';
		} else {
			$rows = explode('&', Request::input('order'));
			$precedence = 1;
			foreach ($rows as $instance) {
				list($garbage, $row_id) = explode('=', $instance);
				if (!empty($row_id)) {
					DB::table($table->name)->where('id', $row_id)->update(['precedence'=>$precedence++]);
				}
			}
			return 'done reordering ' . Request::input('order')  . ' instances, linear';
		}
	}
	
	# Soft delete
	public function delete($table, $row_id) {
		$object = DB::table(config('center.db.objects'))->where('name', $table)->first();
		
		//toggle instance with active or inactive
		$deleted_at = (Request::input('active') == 1) ? null : new DateTime;

		DB::table($table->name)->where('id', $row_id)->update(array(
			'deleted_at'=>$deleted_at,
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id,
		));

		//update object meta
		DB::table(config('center.db.objects'))->where('id', $object->id)->update(array(
			'count'=>DB::table($table->name)->whereNull('deleted_at')->count(),
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id,
		));

		$updated = DB::table($table->name)->where('id', $row_id)->pluck('updated_at');

		return \LeftRight\Center\Libraries\Dates::relative($updated);
	}

	# Sanitize field values before inserting
	private function sanitize($field) {
		
		//foreign key situation, exit
		if (in_array($field->type, ['checkboxes', 'images'])) return;

		//trim whitespace
		$value = trim(Request::input($field->name));

		//add each field if not present
		if ($field->type == 'checkbox') {
			
			$value = !empty($value); //true or false, never null
		
		} elseif (empty($value) && ($value !== '0') && !$field->required) {
		
			$value = null; //nullable
		
		} else {

			//format date fields
			if ($field->type == 'date') $value = date('Y-m-d', strtotime($value));

			//format date fields
			if ($field->type == 'datetime') $value = date('Y-m-d H:i:s', strtotime($value));

			//format slug fields
			if ($field->type == 'slug') $value = Str::slug($value);

		}

		return $value;
	}

	# Recursively assemble nested tree
	private function nestedNodeExists(&$array, $parent_id, $child) {
		foreach ($array as &$a) {
			if ($a->id == $parent_id) {
				$a->children[] = $child;
				return true;
			} elseif (count($a->children) && self::nestedNodeExists($a->children, $parent_id, $child)) {
				return true;
			}
		}
		return false;
	}

	# Return a foreign key column name for a given table name or object_id (public for AvalonServiceProvider::boot)
	public static function getKey($table_name) {
		if (ctype_digit(strval($table_name))) $table_name = DB::table(config('center.db.objects'))->where('id', $table_name)->pluck('name');
		return Str::singular($table_name) . '_id';
	}

	# Get related object with the first string field name
	private static function getRelatedObject($related_object_id) {
		$related = DB::table(config('center.db.objects'))->where('id', $related_object_id)->first();
		$related->field = DB::table(config('center.db.fields'))->where('object_id', $related_object_id)->whereIn('type', ['string', 'text'])->first();
		return $related;
	}

	# Get related object's name with an object
	private static function getRelatedObjectName($object) {
		return DB::table(config('center.db.fields'))
			->join(config('center.db.objects'), config('center.db.fields') . '.related_object_id', '=', config('center.db.objects') . '.id')
			->where(config('center.db.fields') . '.id', $object->group_by_field)
			->pluck(config('center.db.objects') . '.name');
	}

	# Draw an instance table, used both by index and by edit > linked
	public static function table($table, $columns, $rows) {
		if (count($rows)) {
			$return = new \LeftRight\Center\Libraries\Table;
			$return->rows($rows);
			foreach ($columns as $column) {
				$return->column($column->name, $column->type, $column->title);
			}
			if ($table->user_can_edit) {
				$return->deletable();
				if ($table->order_by == 'precedence') $return->draggable(action('\LeftRight\Center\Controllers\RowController@reorder', $table));
			}
			if (!empty($table->group_by_field)) $return->groupBy('group');
			return $return->draw($table->name);
		}
	}

	//export instances
	public function export($table) {

		$table = config('center.tables.' . $table);

		Excel::create($table->title, function($excel) use ($table) {

		    $excel->setTitle($table->title)->sheet($table->title, function($sheet) use ($object) {
		
					$results = DB::table($table->name)->get();
					$rows = [];
					
					foreach ($results as $result) {
						$row = [];
						foreach ($table->fields as $field) {
							if (in_array($field->type, ['html', 'checkboxes', 'text'])) continue;
							$row[$field->name] = $result->{$field->name};
						}
						$rows[] = $row;
					}
					
					/*format columns
					$sheet->setColumnFormat([
						'E' => '0.00',
					]);*/

					$sheet->with($rows)->freezeFirstRow();
					
					/*
					$sheet->cells('A1:F1', function($cells) {
						$cells->setFontWeight('bold');
					});*/

				});

		})->download('xlsx');
	}
	
	/*
	public function redactor_s3() {

		$S3_KEY		= Config::get('aws.key');
		$S3_SECRET	= Config::get('aws.secret');
		$S3_BUCKET	= Config::get('aws.bucket');
		$S3_URL		= 'http://s3.amazonaws.com';
		$EXPIRE_TIME = (60 * 5); // 5 minutes

		$objectName = '/' . $_GET['name'];
		$mimeType	= $_GET['type'];
		$expires 	= time() + $EXPIRE_TIME;
		$amzHeaders	= "x-amz-acl:public-read";
		$stringToSign = "PUT\n\n$mimeType\n$expires\n$amzHeaders\n$S3_BUCKET$objectName";

		$sig = urlencode(base64_encode(hash_hmac('sha1', $stringToSign, $S3_SECRET, true)));
		$url = urlencode("$S3_URL$S3_BUCKET$objectName?AWSAccessKeyId=$S3_KEY&Expires=$expires&Signature=$sig");
	}

	public static function upload_image($object_id, $row_id) {

		$temp_file = 'temp.dat';

		//resize and save - todo learn how to do facades in a package
		Image::make(Request::file('image_upload')->getRealPath())
				->resize(830, null, true)
				->save($temp_file);

		//send the image to s3
		$s3 = App::make('aws')
			->get('s3')
			->putObject(array(
			    'Bucket'     => Config::get('aws.bucket'),
			    'Key'        => Request::input('filename'),
			    'SourceFile' => $temp_file,
	            'ACL'		 => 'public-read',
			));

		//delete the image
		unlink(base_path() . '/public/' . $temp_file);

		//send a response
		
	}*/
}

