<?php

return [
	//you may add or replace the default bootstrap css
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	
	//the url prefix of the cms
	'prefix' => 'center',
	
	//system tables (keys fixed, values must match system_tables keys)
	'db' => [
		'files' => 'files',
		'metadata' => 'metadata',
		'permissions' => 'permissions',
		'users' => 'users',
	],
	
	//default objects to start
	'system_tables' => [
		'files' => [
			'hidden',
			'fields' => [
				'object' => 'string',
				'field' => 'string',
				'host' => 'string',
				'path' => 'string',
				'name' => 'string',
				'extension' => [
					'type' => 'string',
					'required',
				],
				'width' => 'integer',
				'height' => 'integer',
				'created_at',
				'created_by',
			],
		],
		'metadata' => [
			'hidden',
			'fields' => [
				'table' => [
					'type' => 'string',
					'required',	
				],
				'event' => [
					'type' => 'string',
					'required',	
				],
				'row_id' => [
					'type' => 'integer',
					'required',	
				],
				'created_at',
				'created_by',
			],
		],
		'permissions' => [
			'hidden',
			'fields' =>	[
				'user_id' => [
					'type' => 'integer',
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
				'deleted_at',
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