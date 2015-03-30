<?php namespace LeftRight\Center\Controllers;

use Auth;
use DateTime;
use DB;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Request;

class FileController extends \App\Http\Controllers\Controller {

	/**
	 * handle image upload route
	 */
	public function image() {
		if (Request::hasFile('image') && Request::has('field_id')) {
			return json_encode(self::saveImage(
				Request::input('field_id'), 
				file_get_contents(Request::file('image')),
				Request::file('image')->getClientOriginalName()
			));

		} elseif (!Request::hasFile('image')) {
			return 'no image';
		} elseif (!Request::hasFile('field_id')) {
			return 'no field_id';
		} else {
			return 'neither image nor field_id';			
		}
	}

	/**
	 * genericized function to handle upload, available externally via service provider
	 */
	public static function saveImage($field_id, $file, $filename, $instance_id=null) {
		//get field info
		$field 	= DB::table(config('center.db.fields'))->where('id', $field_id)->first();
		$object = DB::table(config('center.db.objects'))->where('id', $field->object_id)->first();
		$unique	= Str::random(5);

		//make path
		$path = '/' . implode('/', array(
			'packages',
			'joshreisner',
			'avalon',
			'files',
			$object->name,
			$field->name,
			$unique,
		));

		//also make path in the filesystem
		mkdir(public_path() . $path, 0777, true);

		//get name and extension
		$fileparts = pathinfo($filename);
		$filename	= Str::slug($fileparts['filename'], '-');
		$extension 	= strtolower($fileparts['extension']);
		$file_path 	= $path . '/' . $filename . '.' . $extension;

		//process and save image
		if (!empty($field->width) && !empty($field->height)) {
			Image::make($file)
				->fit((int)$field->width, (int)$field->height)
				->save(public_path() . $file_path);
		} elseif (!empty($field->width)) {
			Image::make($file)
				->widen((int)$field->width)
				->save(public_path() . $file_path);
		} elseif (!empty($field->height)) {
			Image::make($file)
				->heighten(null, (int)$field->height)
				->save(public_path() . $file_path);
		} else {
			Image::make($file)
				->save(public_path() . $file_path);
		}

		//get dimensions
		list($width, $height, $type, $attr) = getimagesize(public_path() . $file_path);

		//get size
		$size = filesize(public_path() . $file_path);

		//insert record for image
		$file_id = DB::table(config('center.db.files'))->insertGetId(array(
			'field_id' =>		$field->id,
			'instance_id' =>	$instance_id,
			'path' =>			$path,
			'name' =>			$filename,
			'extension' =>		$extension,
			'url' =>			$file_path,
			'width' =>			$width,
			'height' =>			$height,
			'size' =>			$size,
			'writable' =>		1,
			'updated_by' =>		Auth::user()->id,
			'updated_at' =>		new DateTime,
			'precedence' =>		DB::table(config('center.db.files'))->where('field_id', $field->id)->max('precedence') + 1,
		));

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

		//perhaps also screenwidth & screenheight?
		return array(
			'file_id' =>		$file_id, 
			'url' =>			$file_path,
			'width' =>			$width,
			'height' =>			$height,
			'screenwidth' =>	$screenwidth,
			'screenheight' =>	$screenheight,
		);
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
		foreach (new RecursiveIteratorIterator($di) as $filename => $file) {
			if (!ends_with($filename, ['/.', '/..'])) {
				if (!in_array(substr($filename, $public_path_length), $files)) {
					unlink($filename);
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