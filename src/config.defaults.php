<?php
	
return [
	'admins' => [ 1 ],
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	'db' => [
		'files' => 'files',
		'permissions' => 'permissions',
		'users' => 'users',
	],
	'files' => [
		'path' => '/vendor/center/files',
	],
	'icons' => [
		'home' => '<i class="glyphicon glyphicon-home"></i>',
		'breadcrumb' => ' <i class="glyphicon glyphicon-chevron-right"></i> ',
		'create' => '<i class="glyphicon glyphicon-hand-left"></i>',
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