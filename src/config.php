<?php

return [

	//users with these IDs will be added to objects by default
	'admins' => [ 1 ],

	//you may augment or replace the default bootstrap css
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	
	//the url prefix of the cms
	'prefix' => 'center',
	
	//system tables (keys fixed, values must match system_tables keys)
	'db' => [
		'files' => 'files',
		'permissions' => 'permissions',
		'users' => 'users',
	],
	
	//default objects to start
	'system_tables' => [
		'files' => [
			'keep_clean',
			'hidden',
			'model' => 'File',
			'fields' => [
				'row' => 'integer',
				'table' => 'string',
				'field' => 'string',
				'path' => 'string',
				'name' => 'string',
				'extension' => [
					'type' => 'string',
					'required',
				],
				'width' => 'integer',
				'height' => 'integer',
				'size' => 'integer',
				'created_at',
				'created_by',
				'precedence'=>'integer',
			],
		],
		'permissions' => [
			'keep_clean',
			'hidden',
			'fields' =>	[
				'user' => [
					'type' => 'integer',
					'required',
				],
				'table' => [
					'type' => 'string',
					'required',
				],
				'level' => [
					'type' => 'string',
					'required',
				],
			],
		],
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
];