<?php namespace LeftRight\Center\Libraries;

class Breadcrumbs {

	public static function leave($breadcrumbs) {
		$return = array();
		
		//prepend home
		$breadcrumbs = array_merge(['/' => config('center.icons.home')], $breadcrumbs);
		
		//build breadcrumbs
		foreach ($breadcrumbs as $link=>$text) {
			$return[] = (is_string($link)) ? '<a href="' . $link . '">' . $text . '</a>' : $text;
		}
		
		return '<h1>' . implode(' ' . config('center.icons.breadcrumb') . ' ', $return) . '</h1>';
	}
	
}

