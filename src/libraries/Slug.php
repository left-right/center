<?php namespace LeftRight\Center\Libraries;

use DB;
use Illuminate\Support\Str;

class Slug {

	private static $limit = 50;

	private static $separator = '-';

	public static function make($string, $table=[], $exclude_id=0, $column='slug') {

		//die('slug string was ' . $string);

		# $table can be array
		if (is_array($table)) {
			$uniques = array_values($table);
		} else {
			$uniques = DB::table($table)->where('id', '<>', $exclude_id)->lists($column);
			if (empty($uniques)) $uniques = [];
		}


		# Convert string into array of url-friendly words
		$words = explode('-', Str::slug($string, '-'));

		# Reset indexes
		$words = array_values($words);

		# Limit length of slug down to fewer than 50 characters
		while (self::checkLength($words) === false) array_pop($words);

		# Check uniqueness
		while (self::checkUniqueness($words, $uniques) === false) self::increment($words);

		return self::makeFromWords($words);

	}

	public static function source($object_id) {
		return DB::table(config('center.db.fields'))
			->where('object_id', $object_id)
			->whereIn('type', ['string', 'text'])
			->orderBy('precedence')
			->pluck('name');
	}

	private static function makeFromWords($words) {
		return implode(self::$separator, $words);
	}

	private static function checkLength($words) {
		$counts = array_map('strlen', $words);
		return ((array_sum($counts) + count($words) - 1) <= self::$limit);
	}

	private static function increment(&$words) {
		if (empty($words)) {
			//if whole array is empty
			$words = [0];
		} elseif (empty($words[count($words) - 1])) {
			//if final element is empty
			$words[count($words) - 1] = 0;
		} elseif (!is_integer($words[count($words) - 1])) {
			//todo check to see if goes over length limit
			$words[] = 0;
		}
		$words[count($words) - 1]++;
	}

	private static function checkUniqueness($words, $uniques) {
		return !in_array(self::makeFromWords($words), $uniques);
	}

	public static function setForObject($object) {
		$slug_source = self::source($object->id);
		$instances = DB::table($object->name)->get();
		$slugs = [];
		foreach ($instances as $instance) {
			if ($slug_source === null) {
				$slug = self::make($instance->created_at->format('Y-m-d'), $slugs);
			} else {
				$slug = self::make($instance->{$slug_source}, $slugs);
			}
			DB::table($object->name)->where('id', $instance->id)->update(['slug'=>$slug]);
			$slugs[] = $slug;
		}
	}

}