<?php namespace LeftRight\Center\Controllers;

use App;
use Auth;
use DateTime;
use DB;
use Illuminate\Support\Str;
use Input;
use Redirect;
use Request;
use Schema;
use URL;
use View;

class ObjectController extends \App\Http\Controllers\Controller {

	private static $direction = [
		'asc'=>'Ascending',
		'desc'=>'Descending',
	];
	
	# Display list for home page
	public function index() {
		if (!Auth::check()) return LoginController::getIndex();
		
		$objects = DB::table(config('center.db.objects'))
			->join(config('center.db.users'), config('center.db.users') . '.id', '=', config('center.db.objects') . '.updated_by')
			->select(config('center.db.objects') . '.*', config('center.db.users') . '.name AS updated_name')
			->orderBy('list_grouping')
			->orderBy('title')
			->get();
		foreach ($objects as &$object) {
			$object->link = URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name);
			$object->updated_by = $object->name;
			if ($object->count == 0) $object->instance_count = '';
		}
		return view('center::objects.index', compact('objects'));
	}
	
	# Display create object form
	public function create() {
		$order_by = [trans('center::fields.system')=>[
			'id'=>trans('center::fields.id'),
			'precedence'=>trans('center::fields.precedence'),
			'created_at'=>trans('center::fields.created_at'),
			'updated_at'=>trans('center::fields.updated_at'),
		]];

		return View::make('center::objects.create', [
			'order_by' =>$order_by,
			'direction'	=>self::$direction,
			'list_groupings' =>self::getGroupings(),
		]);
	}
	
	# Store create object form post data
	public function store() {

		//make plural, title case
		$title		= mb_convert_case(Str::plural(Request::input('title')), MB_CASE_TITLE, 'UTF-8');

		//determine table name, todo check if unique
		$name		= Str::slug($title, '_');

		//model name
		$model		= Str::singular(Str::studly($title));
		
		//enforce predence always ascending
		$order_by	= Request::input('order_by');
		$direction 	= Request::input('direction');
		if ($order_by == 'precedence') $direction = 'asc';

		//create entry in objects table for new object
		$object_id = DB::table(config('center.db.objects'))->insertGetId([
			'title'			=> $title,
			'name'			=> $name,
			'model'			=> $model,
			'order_by'		=> $order_by,
			'direction'		=> $direction,
			'list_grouping'	=> Request::input('list_grouping'),
			'updated_at'	=> new DateTime,
			'updated_by'	=> Auth::user()->id,
		]);
		
		//create title field for table by default
		try {
			
			DB::table(config('center.db.fields'))->insert([
				'title'			=> 'Title',
				'name'			=> 'title',
				'type'			=> 'string',
				'visibility'	=> 'list',
				'required'		=> 1,
				'object_id'		=> $object_id,
				'updated_at'	=> new DateTime,
				'updated_by'	=> Auth::user()->id,
				'precedence'	=> 1
			]);
	
			self::addTable($name, true);
	
			self::saveSchema();
			
			return Redirect::action('\LeftRight\Center\Controllers\InstanceController@index', $name)
				->with('message', trans('center::objects.created'));
		} catch (\Exception $e) {
			return Redirect::action('\LeftRight\Center\Controllers\InstanceController@index', $name)
				->with('error', $e->getMessage());
		}
	}
	
	# Edit object settings
	public function edit($object_name) {

		//get order by select data
		$object = DB::table(config('center.db.objects'))->where('name', $object_name)->first();
		$fields = DB::table(config('center.db.fields'))->where('object_id', $object->id)->orderBy('precedence')->get();
		$order_by = [];
		foreach ($fields as $field) $order_by[$field->name] = $field->title;
		$order_by = [
			trans('center::fields.system')=>[
				'id'=>trans('center::fields.id'),
				'slug'=>trans('center::fields.slug'),
				'precedence'=>trans('center::fields.precedence'),
				'created_at'=>trans('center::fields.created_at'),
				'updated_at'=>trans('center::fields.updated_at'),
			],
			trans('center::fields.user')=>$order_by,
		];

		//related objects are different than dependencies; it's the subset of dependencies that are grouped by this object
		$related_objects = DB::table(config('center.db.fields'))
			->join(config('center.db.objects'), config('center.db.objects') . '.group_by_field', '=', config('center.db.fields') . '.id')
			->where(config('center.db.fields') . '.related_object_id', $object->id)
			->orderBy(config('center.db.objects') . '.title')
			->select(config('center.db.objects') . '.*') //due to bug that leads to ambiguous column error
			->lists('title', 'id');

		//values for the related objects. could be combined with above
		$links = DB::table(config('center.db.object_links'))->where('object_id', $object->id)->lists('linked_id');

		//return view
		return View::make('center::objects.edit', [
			'object'			=>$object, 
			'order_by'			=>$order_by,
			'direction'			=>self::$direction,
			'dependencies'		=>DB::table(config('center.db.fields'))->where('related_object_id', $object->id)->count(),
			'group_by_field'	=>[''=>''] + DB::table(config('center.db.fields'))->where('object_id', $object->id)->where('type', 'select')->lists('title', 'id'),
			'list_groupings'	=>self::getGroupings(),
			'related_objects'	=>$related_objects,
			'links'				=>$links,
		]);
	}
	
	//edit object settings
	public function update($object_name) {

		//rename table if necessary
		$object = DB::table(config('center.db.objects'))->where('name', $object_name)->first();
		$new_name = Str::slug(Request::input('name'), '_');
		if ($object->name != $new_name) Schema::rename($object->name, $new_name);
		
		//enforce predence always ascending
		$order_by = Request::input('order_by');
		$direction = Request::input('direction');
		if ($order_by == 'precedence') $direction = 'asc';

		//not sure why it's necessary, doesn't like empty value all of a sudden
		$group_by_field = Request::has('group_by_field') ? Request::input('group_by_field') : null;

		//linked objects
		DB::table(config('center.db.object_links'))->where('object_id', $object->id)->delete();
		if (Request::has('related_objects')) {
			foreach (Request::input('related_objects') as $linked_id) {
				DB::table(config('center.db.object_links'))->insert([
					'object_id'=>$object->id,
					'linked_id'=>$linked_id,
				]);
			}
		}

		//update objects table
		DB::table(config('center.db.objects'))->where('id', $object->id)->update([
			'title'				=> Request::input('title'),
			'name'				=> $new_name,
			'model'				=> Request::input('model'),
			'url'				=> Request::input('url'),
			'order_by'			=> $order_by,
			'direction'			=> $direction,
			'singleton'			=> Request::has('singleton') ? 1 : 0,
			'can_see'			=> Request::has('can_see') ? 1 : 0,
			'can_create'		=> Request::has('can_create') ? 1 : 0,
			'can_edit'			=> Request::has('can_edit') ? 1 : 0,
			'list_grouping'		=> Request::input('list_grouping'),
			'group_by_field'	=> $group_by_field,
			'list_help'			=> trim(Request::input('list_help')),
			'form_help'			=> trim(Request::input('form_help')),
		]);

		self::saveSchema();
		
		return Redirect::action('\LeftRight\Center\Controllers\InstanceController@index', $new_name);
	}
	
	//destroy object
	public function destroy($object_name) {
		$object = DB::table(config('center.db.objects'))->where('name', $object_name)->first();
		Schema::dropIfExists($object->name);
		DB::table(config('center.db.objects'))->where('id', $object->id)->delete();
		DB::table(config('center.db.fields'))->where('object_id', $object->id)->delete();
		DB::table(config('center.db.object_links'))->where('object_id', $object->id)->orWhere('linked_id', $object->id)->delete();
		self::saveSchema();
		return Redirect::route('home');
	}

	//for list_grouping typeaheads
	private static function getGroupings() {
		$groupings = DB::table(config('center.db.objects'))->where('list_grouping', '<>', '')->distinct()->orderBy('list_grouping')->lists('list_grouping');
		foreach ($groupings as &$grouping) $grouping = '"' . str_replace('"', '', $grouping) . '"';
		return '[' . implode(',', $groupings) . ']';
	}

	//to create unique table names, also for import controller
	public static function getTables() {
		$return = [];
		$pdo = DB::connection()->getPdo();
		$tables = $pdo->query('SHOW TABLES');
		foreach ($tables as $table) $return[] = array_shift($table);
		return $return;
	}

	//to create unique table names, also for import controller
	public static function getPaths() {
		return ['create', 'import', 'logout'];
	}
	
	//create table with boilerplate fields
	private static function addTable($name, $addTitle=true) {
		Schema::create($name, function($table) use($addTitle){
			$table->increments('id');
			if ($addTitle) $table->string('title');
			$table->string('slug');
			$table->timestamps();
			$table->integer('created_by');
			$table->integer('updated_by');
			$table->softDeletes();
			$table->integer('precedence');
		});		
	}
	
	public static function saveSchema() {
		if (!App::environment('local')) return;
		$filename = storage_path() . '/avalon.schema.json';
		$schema = [
			'generated'=>new DateTime,
			'objects'=>DB::table(config('center.db.objects'))->get(),
			'fields'=>DB::table(config('center.db.fields'))->get(),
		];
		file_put_contents($filename, json_encode($schema));
		return;
	}
	
	//load schema from file
	public static function loadSchema() {
		
		//run this first, because any orphaned fields will cause it to squawk
		FieldController::cleanup();
		
		//load schema from file and prepare
		$schema = json_decode(file_get_contents(storage_path() . '/avalon.schema.json'));

		//load current database into $objects and $fields variables
		$objects = $fields = [];
		$db_fields = DB::table(config('center.db.fields'))
			->join(config('center.db.objects'), config('center.db.objects') . '.id', '=', config('center.db.fields') . '.object_id')
			->select(
				config('center.db.fields') . '.object_id',
				config('center.db.fields') . '.id AS field_id', 
				config('center.db.objects') . '.name AS table', 
				config('center.db.fields') . '.name AS column'
			)->get();
		foreach ($db_fields as $field) {
			if (!array_key_exists($field->object_id, $objects)) $objects[$field->object_id] = $field->table;
			$fields[$field->field_id] = ['table'=>$field->table, 'column'=>$field->column];
		}

		//loop through new object schema and update
		foreach ($schema->objects as $object) {

			$values = [
				'id'=>$object->id,
				'title'=>$object->title,
				'name'=>$object->name,
				'model'=>$object->model,
				'order_by'=>$object->order_by,
				'direction'=>$object->direction,
				'group_by_field'=>$object->group_by_field,
				'list_help'=>$object->list_help,
				'form_help'=>$object->form_help,
				'list_grouping'=>$object->list_grouping,
				'can_create'=>$object->can_create,
				'can_edit'=>$object->can_edit,
				'can_see'=>$object->can_see,
				'url'=>$object->url,
				'singleton'=>$object->singleton,
			];

			if (array_key_exists($object->id, $objects)) {
				DB::table(config('center.db.objects'))->where('id', $object->id)->update($values);
			} else {
				DB::table(config('center.db.objects'))->insert($values);
				self::addTable($object->name);
			}
			
			if (isset($objects[$object->id])) unset($objects[$object->id]);
		}
		
		foreach ($objects as $id=>$table) {
			DB::table(config('center.db.objects'))->where('id', $id)->delete();
			DB::table(config('center.db.fields'))->where('object_id', $id)->delete();
			Schema::dropIfExists($table);
		}
		
		foreach ($schema->fields as $field) {

			$values = [
				'id'=>$field->id,
				'object_id'=>$field->object_id,
				'type'=>$field->type,
				'title'=>$field->title,
				'name'=>$field->name,
				'visibility'=>$field->visibility,
				'required'=>$field->required,
				'related_field_id'=>$field->related_field_id,
				'related_object_id'=>$field->related_object_id,
				'width'=>$field->width,
				'height'=>$field->height,
				'help'=>$field->help,
				'updated_at'=>$field->updated_at,
				'updated_by'=>$field->updated_by,
				'precedence'=>$field->precedence,
			];
			
			if ($field->id == 62) {
				dd($field);
			}
			
			if (array_key_exists($field->id, $fields)) {
				DB::table(config('center.db.fields'))->where('id', $field->id)->update($values);
			} else {
				DB::table(config('center.db.fields'))->insert($values);
				if ($field->type == 'checkboxes') {
					FieldController::addJoiningTable($fields[$field->id]['table'], $field->related_object_id);
				} else {
					FieldController::addColumn($fields[$field->id]['table'], $field->name, $field->type, $field->required);			
				}
			}
			
			if (isset($fields[$field->id])) unset($fields[$field->id]);
		}
		
		foreach ($fields as $id=>$props) {
			extract($props);
			DB::table(config('center.db.fields'))->where('id', $id)->delete();
			Schema::dropIfExists($table, $column);
		}

	}
	
}