<?php
	
return [
	'admins'=> [ 1 ],
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	'db' => [
		'files'			=> 'files',
		'permissions'	=> 'permissions',
		'users'			=> 'users',
	],
	'files' => [
		'path'			=> '/vendor/center/files',
	],
	'icons' => [
		'breadcrumb'	=> '<i class="glyphicon glyphicon-chevron-right"></i>',
		'create'		=> '<i class="glyphicon glyphicon-plus"></i>',
		'date'			=> '<i class="glyphicon glyphicon-calendar"></i>',
		'deleted'		=> '<i class="glyphicon glyphicon-remove-circle"></i>',
		'drag'			=> '<i class="glyphicon glyphicon-align-justify"></i>',
		'export'		=> '<i class="glyphicon glyphicon-circle-arrow-down"></i>',
		'home'			=> '<i class="glyphicon glyphicon-home"></i>',
		'new_window'	=> '<i class="glyphicon glyphicon-new-window"></i>',
		'permissions'	=> '<i class="glyphicon glyphicon-user"></i>',
		'phone'			=> '<i class="glyphicon glyphicon-phone"></i>',
		'time'			=> '<i class="glyphicon glyphicon-time"></i>',
		'undeleted'		=> '<i class="glyphicon glyphicon-ok-circle"></i>',
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
	'js' => [
		'/vendor/center/js/main.min.js',	
	],
	'prefix' => 'center',
];