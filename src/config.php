<?php

return [
	//you may add or replace the default bootstrap css
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	
	//the url prefix of the cms
	'prefix' => 'center',
	
	//system tables
	'db' => [
		'files' => 'files',
		'permissions' => 'permissions',
		'users' => 'users',
	],
	
	//default objects to start
	'tables' => [
		'files' => [
			'hidden',
			'fields' => [
				'id' => 'id',
				'object' => 'string',
				'field' => 'string',
				'host' => 'string',
				'path' => 'string',
				'name' => 'string',
				'extension' => 'string',
				'width' => 'int',
				'height' => 'int',
				'created_at' => 'datetime',
				'created_by' => 'int',
			],
		],
		'permissions' => [
			'hidden',
			'fields' =>	[
				'id' => 'id',
				'user_id' => [
					'type' => 'int',
					'required',
				],
				'object' => [
					'type' => 'string',
					'required',
				],
				'type' => [
					'type' => 'string',
					'required',
				],
			],
		],
		'users' => [
			'list'=> ['name', 'last_login', 'updated_at'],
			'order_by' => 'name',
			'fields' => [
				'id' => 'id',
				'name' => [
					'type' => 'string',
					'required',
				],
				'email' => [
					'type' => 'email',
					'required',
				],
				'password' => 'password',
				'remember_token' => [
					'type' => 'string',
					'hidden',
				],
				'last_login' => [
					'type' => 'datetime',
					'hidden',
				],
				'admin' => 'checkbox',
				'updated_at',
				'updated_by',
				'deleted_at',
				'deleted_by',
			],
		],
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