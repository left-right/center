<?php namespace LeftRight\Center\Controllers;

use Auth;
use DateTime;
use DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Request;
use Session;

class FileController extends Controller {


	//test upload route
	public function test() {
		return '<form action="' . action('\LeftRight\Center\Controllers\FileController@image') . '" method="post" enctype="multipart/form-data">
			<input type="hidden" name="_token" value="' . Session::token() . '">
			<input type="hidden" name="table_name" value="books">
			<input type="hidden" name="field_name" value="cover">
			<input type="file" name="image">
			<input type="submit">
		</form>';	
	}
	
	/**
	 * handle image upload route
	 */
	public function image() {
		if (Request::hasFile('image') && Request::has('table_name') && Request::has('field_name')) {
			return json_encode(self::saveImage(
				Request::input('table_name'), 
				Request::input('field_name'), 
				file_get_contents(Request::file('image')),
				Request::file('image')->getClientOriginalName()
			));

		} elseif (!Request::hasFile('image')) {
			return 'no image';
		} elseif (!Request::hasFile('table_name')) {
			return 'no table_name';
		} elseif (!Request::hasFile('field_name')) {
			return 'no field_name';
		}
	}

	/**
	 * genericized function to handle upload, available externally via service provider
	 */
	public static function saveImage($table_name, $field_name, $file, $name, $instance_id=null) {
		//get field info
		$table 	= config('center.tables.' . $table_name);
		$field = $table->fields->{$field_name};
		$unique	= Str::random(5);

		//make path
		$path = implode('/', [
			'/vendor/center/files',
			$table_name,
			$unique,
		]);

		//also make path in the filesystem
		mkdir(public_path() . $path, 0777, true);

		//get name and extension
		$parts		= pathinfo($name);
		$name		= $field->name;
		$extension 	= strtolower($parts['extension']);
		$url		= $path . '/' . $name . '.' . $extension;

		//process and save image
		if (!empty($field->width) && !empty($field->height)) {
			Image::make($file)
				->fit((int)$field->width, (int)$field->height)
				->save(public_path() . $url);
		} elseif (!empty($field->width)) {
			Image::make($file)
				->widen((int)$field->width)
				->save(public_path() . $url);
		} elseif (!empty($field->height)) {
			Image::make($file)
				->heighten(null, (int)$field->height)
				->save(public_path() . $url);
		} else {
			Image::make($file)
				->save(public_path() . $url);
		}

		//get dimensions
		list($width, $height, $type, $attr) = getimagesize(public_path() . $url);

		//get size
		$size = filesize(public_path() . $url);

		//insert record for image
		$file_id = DB::table(config('center.db.files'))->insertGetId([
			'table' =>			$table->name,
			'field' =>			$field->name,
			'path' =>			$path,
			'name' =>			$name,
			'extension' =>		$extension,
			'width' =>			$width,
			'height' =>			$height,
			'size' =>			$size,
			'created_at' =>		new DateTime,
			'created_by' =>		Auth::user()->id,
			'precedence' =>		DB::table(config('center.db.files'))
				->where('table', $table->name)
				->where('field', $field->name)
				->max('precedence') + 1,
		]);

		/*push it over to s3
		$target = Str::random() . '/' . Request::file('image')->getClientOriginalName();
		$bucket = 'josh-reisner-dot-com';
		AWS::get('s3')->putObject(array(
		    'Bucket' => 		$bucket,
		    'Key' => 			$target,
		    'SourceFile' => 	$temp,
		));
		unlink($file);
		return 'https://s3.amazonaws.com/' . $bucket . '/' . $target;
		*/

		list($screenwidth, $screenheight) = self::getImageDimensions($width, $height);

		return [
			'file_id' =>		$file_id, 
			'url' =>			$url,
			'width' =>			$width,
			'height' =>			$height,
			'screenwidth' =>	$screenwidth,
			'screenheight' =>	$screenheight,
		];
	}

	# Get display size for create and edit views
	public static function getImageDimensions($width=false, $height=false) {

		$max_width  = config('center.img.max.width');
		$max_height = config('center.img.max.height');
		$max_area   = config('center.img.max.area');

		//too wide?
		if ($width && $width > $max_width) {
			if ($height) $height *= $max_width / $width;
			$width = $max_width;
		}

		//too tall?
		if ($height && $height > $max_height) {
			if ($width) $width *= $max_height / $height;
			$height = $max_height;
		}

		//not specified?
		if (!$width) $width = config('center.img.default.width');
		if (!$height) $height = config('center.img.default.height');

		//too large?
		$area = $width * $height;
		if ($width * $height > $max_area) {
			$width *= $max_area / $area;
			$height *= $max_area / $area;
		}

		return array($width, $height);
	}

	public static function findOrphans() {
		
		//trim down file list
		
		//delete files from non-existent fields
		DB::table(config('center.db.files'))
			->leftJoin(config('center.db.fields'), config('center.db.files') . '.field_id', '=', config('center.db.fields') . '.id')
			->whereNull(config('center.db.fields') . '.id')
			->delete(); 
			
		//delete files from non-existent instances
		$file_ids = [];
		$fields = DB::table(config('center.db.files'))
			->join(config('center.db.fields'), config('center.db.files') . '.field_id', '=', config('center.db.fields') . '.id')
			->join(config('center.db.objects'), config('center.db.fields') . '.object_id', '=', config('center.db.objects') . '.id')
			->select(
				config('center.db.fields') . '.id',
				config('center.db.objects') . '.name as table',
				config('center.db.fields') . '.name as column'
			)
			->distinct()
			->get();
		foreach ($fields as $field) {
			$file_ids = array_merge($file_ids, DB::table($field->table)->lists($field->column));
		}
		if (count($file_ids)) {
			DB::table(config('center.db.files'))->whereNotIn('id', $file_ids)->delete();			
		}
		
		//trim down filesystem to just what's in file list
		$files = DB::table(config('center.db.files'))->lists('url');
		$public_path_length = strlen(public_path());
		$folder = public_path() . '/packages/joshreisner/avalon/files';
		$di = new RecursiveDirectoryIterator($folder);
		foreach (new RecursiveIteratorIterator($di) as $name => $file) {
			if (!ends_with($name, ['/.', '/..'])) {
				if (!in_array(substr($name, $public_path_length), $files)) {
					unlink($name);
				}
			}
		}
		
		//remove empty folders
		self::removeEmptyFolders($folder);

	}
	
	private static function removeEmptyFolders($path) {
		$empty = true;
		foreach (glob($path.DIRECTORY_SEPARATOR . '*') as $file) {
			$empty &= is_dir($file) && self::removeEmptyFolders($file);
		}
		return $empty && rmdir($path);
	}

	//todo amazon?
	public static function cleanup($files=false) {

		//by default, remove all non-instanced files
		if (!$files) $files = DB::table(config('center.db.files'))->whereNull('instance_id')->get();
		
		//try to physically remove
		$ids = array();
		foreach ($files as $file) {
			$ids[] = $file->id;
			if ($file->writable) {
				@unlink(public_path() . $file->path . '/' . $file->name . '.' . $file->extension);
				@rmdir(public_path() . $file->path);
			}
		}

		//remove records
		if (!empty($ids)) {
			DB::table(config('center.db.files'))->whereIn('id', $ids)->delete();
		}
	}

	private static function formatBytes($size, $precision=2) {
		$base = log($size, 1024);
		$suffixes = array('', 'k', 'M', 'G', 'T');
		return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
	}
}