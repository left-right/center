<?php

return [
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	'db' => [
		'files'			=> 'center_files',
		'fields'		=> 'center_fields',
		'objects'		=> 'center_objects',
		'object_links'	=> 'center_object_links',
		'object_user'	=> 'center_object_user',
		'users'			=> 'center_users',
	],
	'img' => [
		'default' => [
			'width'		=> 220,
			'height'	=> 100,
		],
		'max' => [
			'width'		=> 701,
			'height'	=> 240,
			'area'		=> 168240, //701 * 240 = 168240
		],
	],
	'prefix' => 'center',
];