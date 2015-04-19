<?php namespace LeftRight\Center\Libraries;

use Session;
use URL;

class Trail {
	
	//manage the return stack
	public static function manage() {

		$back = (Session::has('back')) ? Session::get('back') : [];

		if (URL::current() == end($back)) {
			//going back down the stack
			array_pop($back);
		} elseif (URL::previous() != end($back)) {
			//going up the stack
			$back[] = URL::previous();
		}

		//persist
		if (count($back)) {
			Session::set('back', $back);
		} elseif (Session::has('back')) {
			Session::forget('back');
		}

	}
	
	//get last item, or $default if not present
	public static function last($default) {
		if (!Session::has('back')) return $default;
		$back = Session::get('back');
		return array_pop($back);
	}
	
	//clear the stack
	public static function clear() {
		Session::forget('back');
	}
	
}