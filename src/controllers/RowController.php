<?php namespace LeftRight\Center\Controllers;

use Auth;
use App;
use Aws\Common\Enum\Region;
use Aws\Laravel\AwsServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use DateTime;
use DB;
use Exception;
use Hash;
use LeftRight\Center\Libraries\Slug;
use LeftRight\Center\Libraries\Table;
use LeftRight\Center\Libraries\Trail;
use LeftRight\Center\Controllers\LoginController;
use Mail;
use Barryvdh\DomPDF\Facade as PDF;
use Redirect;
use Request;
use Session;
use URL;
use Validator;

class RowController extends \App\Http\Controllers\Controller {

	# Need these for processing input
	private static $relation_field_types = ['checkboxes', 'images', 'permissions'];


	# Show list of instances for an object
	# $linked_field and $linked_row are for when coming from a linked object
	public function index($table_name, $linked_field=false, $linked_row=false) {

		# Get info about the object
		$table = config('center.tables.' . $table_name);
		//dd($table);
		
		# Trail
		if (!$linked_field) Trail::clear();
		
		# Security -- todo hidden?
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'view')) {
			if ($linked_field && $linked_row) return false;
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.no_permissions_view'));
		}

		# Custom index?
		//if ($table->index) return call_user_func($table->index);
		
		# Start query
		$rows = DB::table($table->name);

		# Table columns
		$columns = [];
		
		# Build select statement
		$rows->select([$table->name . '.id']);
		foreach ($table->list as $field) {
			if ($field == $linked_field) continue;
			$field = $table->fields->{$field};
			if ($field->type == 'checkboxes') {
				$rows->addSelect(DB::raw('(SELECT GROUP_CONCAT(' . $field->source . '.' . self::listColumn($field->source) . ' SEPARATOR ", ") 
					FROM ' . $field->source . ' 
					JOIN ' . $field->name . ' ON ' . $field->source . '.id = ' . $field->name . '.' . self::formatKeyColumn($field->source) . '
					WHERE ' . $field->name . '.' . self::formatKeyColumn($table->name) . ' = ' . $table->name . '.id 
					ORDER BY ' . $field->source . '.' . self::listColumn($field->source) . ') AS ' . $field->name));
			} elseif ($field->type == 'image') {
				$rows
					->leftJoin(config('center.db.files'), $table->name . '.' . $field->name, '=', config('center.db.files') . '.id')
					->addSelect(config('center.db.files') . '.url AS ' . $field->name . '_url');
			} elseif (in_array($field->type, ['select', 'user'])) {
				$rows
					->leftJoin($field->source, $field->name, '=', $field->source . '.id')
					->addSelect($field->source . '.' . self::listColumn($field->source) . ' AS ' . $field->name);
			} else {
				//normal, selectable field
				$rows->addSelect($table->name . '.' . $field->name);
			}
			
			//add to table columns
			$columns[] = $field;
		}

		# Handle group-by fields
		$table->nested = false;
		if (!empty($table->group_by)) {
			if (strpos($table->group_by, '::')) {
				list($object, $method) = explode('::', $table->group_by);
				if (class_exists($object)) {
					//call arbitrary user function
					$rows = call_user_func($table->group_by, $rows);
				}
			} else {
				$grouped_table = config('center.tables.' . $table->fields->{$table->group_by}->source);
				$grouped_field = self::listColumn($grouped_table->name);
				if ($grouped_table->name == $table->name) {
					//nested object
					$table->nested = true;
				} else {
					# Include group_by_field in resultset
					foreach ($grouped_table->order_by as $order_by => $direction) {
						$rows->orderBy($order_by, $direction);				
					}
					$rows->leftJoin($grouped_table->name, $table->name . '.' . $table->fields->{$table->group_by}->name, '=', $grouped_table->name . '.id');
					$rows->addSelect($grouped_table->name . '.' . $grouped_field . ' as group');
				}
			}
		}

		# If linked, limit scope to just the linked row
		if ($linked_field && $linked_row) {
			$rows->where($table->name . '.' . $linked_field, $linked_row);
		}

		# Set the order and direction
		foreach ($table->order_by as $order_by => $direction) {
			$rows->orderBy($order_by, $direction);
		}

		# Soft deletes?
		if (isset($table->fields->deleted_at)) {
			$rows->addSelect($table->name . '.deleted_at');
		}

		$searching = false;

		# Text search?
		if ($table->search && Request::has('search')) {
			$searching = true;
			$rows->where(function($query) use($table) {
				foreach ($table->search as $field) {
					$query->orWhere($table->name . '.' . $field, 'LIKE', '%' . Request::input('search') . '%');
				}
			});
		}
		
		# Filter search?
		foreach ($table->filters as $filter) {
			if (Request::has($filter)) {
				$searching = true;
				$rows->where($table->name . '.' . $filter, Request::input($filter));
			}
		}

		# Run query and save it to a variable
		$rows = $rows->paginate(50);

		# Set URLs on each instance
		if (LoginController::checkPermission($table->name, 'edit')) {
			foreach ($rows as &$row) {
				$row->link = action('\LeftRight\Center\Controllers\RowController@edit', [$table->name, $row->id, $linked_field, $linked_row]);
				$row->delete = action('\LeftRight\Center\Controllers\RowController@delete', [$table->name, $row->id]);
			}
		}

		# If it's a nested object, nest-ify the resultset
		if ($table->nested) {
			$list = array();
			foreach ($rows as &$row) {
				$row->children = array();
				if (empty($row->{$grouped_field->name})) { //$grouped_field->name is for ex parent_id
					$list[] = $row;
				} elseif (self::nestedNodeExists($list, $row->{$grouped_field->name}, $row)) {
					//attached child to parent node
				} else {
					//an error occurred; a parent should exist but is not yet present
				}
			}
			$rows = $list;
		}

		# Search filters for the sidebar
		$filters = [];
		foreach ($table->filters as $filter) {
			if ($table->fields->{$filter}->type == 'us_state') {
				$filters[$filter] = ['' => $table->fields->{$filter}->title] + trans('center::site.us_states');;
			} elseif ($table->fields->{$filter}->type == 'country') {
				$filters[$filter] = ['' => $table->fields->{$filter}->title] + trans('center::site.countries');;
			} else {
				$related_table = config('center.tables.' . $table->fields->{$filter}->source);
				$options = DB::table($related_table->name);
				foreach ($related_table->order_by as $column => $direction) {
					$options->orderBy($column, $direction);
				}
				$options->whereIn('id', DB::table($table->name)->distinct()->lists($filter));
				$options = $options->lists(self::listColumn($related_table), 'id');
				
				$filters[$filter] = ['' => $related_table->title] + $options;				
			}
		}
				
		$return = compact('table', 'columns', 'rows', 'filters', 'searching', 'linked_field', 'linked_row');

		# Return array to edit()
		if ($linked_field && $linked_row) return $return;

		# Return HTML view
		return view('center::rows.index', $return);
	}

	//show create form for an object instance
	public function create($table, $linked_field=false, $linked_row=false) {
		$tables = config('center.tables');
		$table = config('center.tables.' . $table);

		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'create') || !$table->creatable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_create'));
		}
		
		$options = [];
		
		# Add to return stack
		Trail::manage();

		foreach ($table->fields as $field) {
			if (($field->type == 'checkboxes') || ($field->type == 'select')) {

				//load options for checkboxes or selects
				$field->options = DB::table($tables[$field->source]->name);
				foreach ($tables[$field->source]->order_by as $column => $direction) {
					$field->options->orderBy($column, $direction);
				}
				$field->options = $field->options->lists(self::listColumn($field->source), 'id');

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
			} elseif ($field->type == 'permissions') {
				$field->tables = array_where(config('center.tables'), function($key, $value) {
					return !in_array($value->name, [config('center.db.files'), config('center.db.permissions')]);					
				});
				$field->options = LoginController::getPermissionLevels();
			} elseif ($field->type == 'user') {
				$field->options = DB::table(config('center.db.users'))->orderBy('name')->lists('name', 'id');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif ($field->type == 'us_state') {
				$field->options = trans('center::site.us_states');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif ($field->type == 'country') {
				$field->options = trans('center::site.countries');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif (in_array($field->type, array('image', 'images'))) {
				list($field->screen_width, $field->screen_height) = FileController::getImageDimensions($field->width, $field->height);
			}
		}

		return view('center::rows.create', compact('table', 'linked_field', 'linked_row'));
	}

	//save a new object instance to the database
	public function store($table) {
		$table = config('center.tables.' . $table);

		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'create') || !$table->creatable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_create'));
		}

		$inserts = self::processColumnsInput($table);

		/*validate
		$v = Validator::make(Request::all(), [
		    'email' => 'required|unique:users|max:255',
		]);
		
		if ($v->fails()) {
		    return redirect()->back()->withInput()->withErrors($v->errors());
		}*/
		
		//if users table, and if permissions, sent invitation
		if ($table->name == config('center.db.users') && Request::has('permissions')) {
			if (count(array_diff(array_values(Request::get('permissions')), ['']))) {
				$password = Str::random(12);
				$inserts['password'] = Hash::make($password);
				$email = $inserts['email'];
				$link = action('\LeftRight\Center\Controllers\TableController@index');
				Mail::send('center::emails.welcome', ['email'=>$email, 'password'=>$password, 'link'=>$link], function($message) use ($email) {
					$message->to($email)->subject(trans('center::site.welcome_email_subject'));
				});
			}
		}

		//run insert
		try {
			$row_id = DB::table($table->name)->insertGetId($inserts);		
		} catch (Exception $e) {
			return redirect()->back()->withInput()->with('error', $e->getMessage());
		}
		
		//handle any checkboxes, had to wait for row_id
		self::processRelationsInput($table, $row_id);

		//clean up any abandoned files
		FileController::cleanup();
		
		//return to last line in stack or object index
		return redirect(Trail::last(action('\LeftRight\Center\Controllers\RowController@index', $table->name)));
	}
	
	//show edit form
	public function edit($table, $row_id, $linked_field=false, $linked_row=false) {
	
		# Get object / field / whatever infoz
		$table = config('center.tables.' . $table);
		$tables = config('center.tables');

		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'edit') || !$table->editable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_edit'));
		}
		
		# Retrieve instance/row values
		$row = DB::table($table->name)->where('id', $row_id)->first();
		
		# Add to return stack
		Trail::manage();

		# Format instance values for form
		foreach ($table->fields as $field) {
			if ($field->type == 'datetime') {
				if (!empty($row->{$field->name})) $row->{$field->name} = date('m/d/Y h:i A', strtotime($row->{$field->name}));
			} elseif (($field->type == 'checkboxes') || ($field->type == 'select')) {

				//load options for checkboxes or selects
				$field->options = DB::table($tables[$field->source]->name);
				foreach ($tables[$field->source]->order_by as $order_by=>$direction) {
					$field->options->orderBy($order_by, $direction);
				}
				$field->options = $field->options->lists(self::listColumn($field->source), 'id');

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
					$table_key = self::formatKeyColumn($table->name);
					$foreign_key = self::formatKeyColumn($field->source);
					$row->{$field->name} = DB::table($field->name)->where($table_key, $row->id)->lists($foreign_key);
				}
			} elseif ($field->type == 'image') {
				$row->{$field->name} = DB::table(config('center.db.files'))->where('id', $row->{$field->name})->first();
				if (!empty($row->{$field->name}->width) && !empty($row->{$field->name}->height)) {
					$field->width = $row->{$field->name}->width;
					$field->height = $row->{$field->name}->height;
				}
				list($field->screen_width, $field->screen_height) = FileController::getImageDimensions($field->width, $field->height);
			} elseif ($field->type == 'images') {
				$row->{$field->name} = DB::table(config('center.db.files'))->where('field_id', $field->id)->where('instance_id', $row->id)->orderBy('precedence', 'asc')->get();
				foreach ($row->{$field->name} as &$image) {
					if (!empty($image->width) && !empty($image->height)) {
						$image->screen_width = $image->width;
						$image->screen_width = $image->height;
					}
				}
				list($field->screen_width, $field->screen_height) = FileController::getImageDimensions($field->width, $field->height);
			} elseif ($field->type == 'permissions') {
				$field->tables = array_where(config('center.tables'), function($key, $value) {
					return !in_array($value->name, [config('center.db.files'), config('center.db.permissions')]);
				});
				
				$permissions = LoginController::permissions($row_id);
				
				foreach ($field->tables as $permissions_table) {
					$permissions_table->value = isset($permissions[$permissions_table->name]) ? $permissions[$permissions_table->name] : '';
				}

				$field->options = LoginController::getPermissionLevels();
			} elseif ($field->type == 'slug') {
				if ($field->required && empty($row->{$field->name}) && $field->related_field_id) {
					//slugify related field to populate this one
					foreach ($fields as $related_field) {
						if ($related_field->id == $field->related_field_id) {
							$row->{$field->name} = Str::slug($row->{$related_field->name});
						}
					}
				}
			} elseif ($field->type == 'user') {
				$field->options = DB::table(config('center.db.users'))->orderBy('name')->lists('name', 'id');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif ($field->type == 'us_state') {
				$field->options = trans('center::site.us_states');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			} elseif ($field->type == 'country') {
				$field->options = trans('center::site.countries');
				if (!$field->required) $field->options = [''=>''] + $field->options;
			}
		}

		// Get linked objects
		$links = [];
		foreach ($table->links as $table_name => $field_name) {
			if ($linked_array = self::index($table_name, $field_name, $row_id)) $links[] = $linked_array;
		}

		return view('center::rows.edit', compact('table', 'row', 'links', 'linked_field', 'linked_row'));
	}
	
	# Save edits to database
	public function update($table, $row_id) {
		$table = config('center.tables.' . $table);
		
		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'edit') || !$table->editable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_edit'));
		}

		//sanitize and convert input to array
		$updates = self::processColumnsInput($table, $row_id);
				
		//run update
		try {
			DB::table($table->name)->where('id', $row_id)->update($updates);
		} catch (Exception $e) {
			return redirect()->back()->withInput()->with('error', $e->getMessage());
		}

		//relations
		self::processRelationsInput($table, $row_id);
		
		//clean up abandoned files
		FileController::cleanup();

		//return to last line in stack or object index
		return redirect(Trail::last(action('\LeftRight\Center\Controllers\RowController@index', $table->name)));
	}
	
	# Remove object from db - todo check for foreign key constraints
	public function destroy($table, $row_id) {
		$table = config('center.tables.' . $table);

		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'edit') || !$table->deletable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_edit'));
		}

		DB::table($table->name)->where('id', $row_id)->delete();

		return Redirect::to(Trail::last(action('\LeftRight\Center\Controllers\RowController@index', $table->name)));
	}
	
	# Reorder fields by drag-and-drop
	public function reorder($table) {
		$table = config('center.tables.' . $table);

		# Security
		if (!isset($table->name)) {
			return;
		} elseif (!LoginController::checkPermission($table->name, 'edit') || !$table->editable) {
			return;
		}

		//determine whether nested
		$table->nested = false;
		if (!empty($table->group_by_field)) {
			$grouped_field = DB::table(config('center.db.fields'))->where('id', $table->group_by_field)->first();
			if ($grouped_field->related_object_id == $table->id) {
				$table->nested = true;
			}
		}

		if ($table->nested) {
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
			foreach ($rows as $row) {
				list($garbage, $row_id) = explode('=', $row);
				if (!empty($row_id)) {
					DB::table($table->name)->where('id', $row_id)->update(['precedence'=>$precedence++]);
				}
			}
			return 'done reordering ' . Request::input('order')  . ' instances, linear';
		}
	}
	
	# Soft delete (permissions-wise this is considered editing)
	public function delete($table, $row_id) {
		$table = config('center.tables.' . $table);

		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'edit') || !$table->editable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_edit'));
		}
		
		//toggle instance with active or inactive
		$deleted_at = (Request::input('active') == 1) ? null : new DateTime;

		//todo check if updated_at and updated_by exist before updating

		DB::table($table->name)->where('id', $row_id)->update([
			'deleted_at'=>$deleted_at,
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id,
		]);

		/*update object meta
		DB::table(config('center.db.objects'))->where('id', $table->id)->update(array(
			'count'=>DB::table($table->name)->whereNull('deleted_at')->count(),
			'updated_at'=>new DateTime,
			'updated_by'=>Auth::user()->id,
		));*/

		$updated = DB::table($table->name)->where('id', $row_id)->pluck('updated_at');

		return \LeftRight\Center\Libraries\Dates::relative($updated);
	}

	# Process Column Input
	private static function processColumnsInput($table, $row_id=false) {

		//metadata
		$return = [];

		if (property_exists($table->fields, 'updated_at')) $return['updated_at'] = new DateTime;
		if (property_exists($table->fields, 'updated_by')) $return['updated_by'] = Auth::user()->id;

		if ($row_id === false) {
			if (property_exists($table->fields, 'created_at')) $return['created_at'] = new DateTime;
			if (property_exists($table->fields, 'created_by')) $return['created_by'] = Auth::user()->id;
			if (property_exists($table->fields, 'precedence')) $return['precedence'] = DB::table($table->name)->max('precedence') + 1;
		}
		
		//loop through the fields
		foreach ($table->fields as $field) {
			if ($field->hidden || in_array($field->type, self::$relation_field_types)) continue;

			//trim whitespace
			$return[$field->name] = trim(Request::input($field->name));

			//first decide whether value is null
			if ($field->type == 'checkbox') {
				
				$return[$field->name] = !empty($return[$field->name]); //checkboxes are true or false, never null
			
			} elseif ($field->type != 'slug' && empty($return[$field->name]) && ($return[$field->name] !== '0') && !$field->required) {
				
				$return[$field->name] = null;

			} else {

				if ($field->type == 'date') {
					
					$return[$field->name] = date('Y-m-d', strtotime($return[$field->name]));
					
				} elseif ($field->type == 'datetime') {
					
					$return[$field->name] = date('Y-m-d H:i:s', strtotime($return[$field->name]));
					
				} elseif ($field->type == 'image') {
	
					if ($row_id) {
						# Unset any old file associations (will get cleaned up later)
						DB::table(config('center.db.files'))
							->where('table', $table->name)
							->where('field', $field->name)
							->where('row_id', $row_id)
							->update(['row_id'=>null]);
					}
	
	
					# Capture the uploaded file by setting the reverse-lookup
					DB::table(config('center.db.files'))
						->where('id', Request::input($field->name))
						->update(['row_id'=>$row_id]);

				} elseif ($field->type == 'phone') {
					
					$return[$field->name] = preg_replace('/[^0-9]/', '', $return[$field->name]);

				} elseif ($field->type == 'slug') {

					//determine where slug is coming from
					if (Request::input($field->name) !== null) { //Request::has() will return false on empty string
						$value = Request::input($field->name);
					} elseif (Request::has($table->fields->{$field->name}->source)) {
						$value = Request::input($table->fields->{$field->name}->source);
					} else {
						$value = date('Y-m-d');
					}
			
					//get other values to check uniqueness
					if ($row_id) {
						$uniques = DB::table($table->name)->where('id', '<>', $row_id)->lists($field->name);						
					} else {
						$uniques = DB::table($table->name)->lists($field->name);						
					}
			
					//add unique, formatted slug to the insert batch
					$return[$field->name] = Slug::make($value, $uniques);
					
				} elseif ($field->type == 'time') {
					
					$return[$field->name] = date('H:i:s', strtotime($return[$field->name]));

				}

			}
		}

		return $return;
	}

	//run relationship updates on input (used by store and update)
	private static function processRelationsInput($table, $row_id) {

		foreach ($table->fields as $field) {
			if ($field->type == 'checkboxes') {
				
				# Figure out schema
				$object_column = self::formatKeyColumn($table->name);
				$remote_column = self::formatKeyColumn($field->source);

				# Clear old values
				DB::table($field->name)->where($object_column, $row_id)->delete();

				# Loop through and save all the checkboxes
				if (Request::has($field->name)) {
					foreach (Request::input($field->name) as $related_id) {
						DB::table($field->name)->insert([
							$object_column=>$row_id,
							$remote_column=>$related_id,
						]);
					}
				}

			} elseif ($field->type == 'images') {

				# Unset any old file associations (will get cleaned up after this loop)
				DB::table(config('center.db.files'))
					->where('table', $table->name)
					->where('field', $field->name)
					->where('row_id', $row_id)
					->update(['instance_id' => null]);

				# Create new associations
				$file_ids = explode(',', Request::input($field->name));
				$precedence = 0;
				foreach ($file_ids as $file_id) {
					DB::table(config('center.db.files'))
						->where('id', $file_id)
						->update([
							'row_id' => $row_id,
							'precedence' => ++$precedence,
						]);
				}

			} elseif ($field->type == 'permissions') {

				if ($table->name == config('center.db.users')) {
					DB::table(config('center.db.permissions'))->where('user_id', $row_id)->delete();
					foreach (Request::input('permissions') as $table_name => $level) {
						if (!empty($level)) {
							DB::table(config('center.db.permissions'))->insert([
								'user_id' => $row_id,
								'table' => $table_name,
								'level' => $level,
							]);
						}
					}
					
					//update permissions if you're updating yourself
					if ($row_id == Auth::id()) LoginController::updateUserPermissions();
				}
			}
		}
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

	//get the first text field column name
	public static function listColumn($table) {
		if (is_string($table)) $table = config('center.tables.' . $table);
		$fields = !empty($table->list) ? $table->list : array_keys((array) $table->fields);
		foreach ($fields as $field) {
			if ($table->fields->{$field}->type == 'string') return $field;
		}
	}

	# Return a foreign key column name for a given table name (also used by CenterServiceProvider)
	public static function formatKeyColumn($table_name) {
		return Str::singular($table_name) . '_id';
	}

	public static function formatJoiningTable($table1, $table2) {
		$table1 = str_singular($table1);
		$table2 = str_singular($table2);
		return (strcmp($table1, $table2) < 0) ? $table1 . '_' . $table2 : $table2 . '_' . $table1;		
	}

	# Draw an instance table, used both by index and by edit > linked
	public static function table($table, $columns, $rows) {
		if (count($rows)) {
			$return = new Table;
			$return->rows($rows);
			foreach ($columns as $column) {
				$return->column($column->name, $column->type, $column->title);
			}
			if (LoginController::checkPermission($table->name, 'edit')) {
				if (isset($table->fields->deleted_at)) $return->deletable();
				if (array_keys($table->order_by)[0] == $table->name . '.precedence') $return->draggable(action('\LeftRight\Center\Controllers\RowController@reorder', $table->name));
			}
			if (!empty($table->group_by)) $return->groupBy('group');
			return $return->draw($table->name);
		}
	}
	
	# Display a PDF for an instance
	public static function pdf($table, $row_id) {

		# Get object / field / whatever infoz
		$table = config('center.tables.' . $table);

		# Security
		if (!isset($table->name)) {
			return redirect()->action('\LeftRight\Center\Controllers\TableController@index')->with('error', trans('center::site.table_does_not_exist'));
		} elseif (!LoginController::checkPermission($table->name, 'edit') || !$table->editable) {
			return redirect()->action('\LeftRight\Center\Controllers\RowController@index', $table->name)->with('error', trans('center::site.no_permissions_edit'));
		}
		
		# Retrieve instance/row values
		$row = DB::table($table->name)->where('id', $row_id)->first();
		
		return PDF::loadView('center::rows.pdf', compact('table', 'row'))
			->setPaper('letter', 'portrait')
			->stream();
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

