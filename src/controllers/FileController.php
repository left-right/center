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
				Request::file('image'),
				null,
				Request::file('image')->getClientOriginalExtension()
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
	 * destroys file after complete
	 */
	public static function saveImage($table_name, $field_name, $file_name, $row_id=null, $extension=null) {
		//get field info
		$table 	= config('center.tables.' . $table_name);
		$field = $table->fields->{$field_name};
		$unique	= Str::random(5);

		//make path
		$path = implode('/', [
			config('center.files.path'),
			$table->name,
			$unique,
		]);

		//also make path in the filesystem
		mkdir(public_path() . $path, 0777, true);

		//get name and extension
		$name		= $field->name;
		$file		= file_get_contents($file_name);
		if (!$extension) $extension = pathinfo($file_name, PATHINFO_EXTENSION);
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

		//try to delete file
		@unlink($file_name);
		
		//try to delete previous value(s)
		if ($row_id) {
			$previous_images = DB::table(config('center.db.files'))
				->where('table', $table->name)
				->where('field', $field->name)
				->where('row_id', $row_id);
			foreach ($previous_images as $previous_image) {
				@unlink($previous_image->url);
			}
		}

		//get dimensions
		list($width, $height, $type, $attr) = getimagesize(public_path() . $url);

		//get size
		$size = filesize(public_path() . $url);

		//insert record for image
		$file_id = DB::table(config('center.db.files'))->insertGetId([
			'table' =>			$table->name,
			'field' =>			$field->name,
			'url' =>			$url,
			'width' =>			$width,
			'row_id' => 		$row_id,
			'height' =>			$height,
			'size' =>			$size,
			'created_at' =>		new DateTime,
			'created_by' =>		Auth::guest() ? null : Auth::user()->id,
			'precedence' =>		DB::table(config('center.db.files'))
				->where('table', $table->name)
				->where('field', $field->name)
				->max('precedence') + 1,
		]);

		//come up with adjusted display size based on user-defined maxima
		list($screenwidth, $screenheight) = self::getImageDimensions($width, $height);
		
		//return array
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
	
	public static function cleanup() {
		$files = DB::table(config('center.db.files'))->lists('url');
		//config('center.files.path')
	}

}