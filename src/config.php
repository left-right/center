<?php

return [
	'css' => [
		'/vendor/center/css/main.min.css',
	],
	'prefix' => 'center',
	'system_tables' => [
		'files' => 'files',
		'permissions' => 'permissions',
		'users' => 'users',
	],
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
				'created_at' => 'created_at',
				'created_by' => 'created_by',
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
			'list'=> ['name', 'last_login'],
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
					'visibility' => 'hidden',
				],
				'created_at' => 'created_at',
				'updated_at' => 'updated_at',
				'deleted_at' => 'deleted_at',
				'created_by' => 'created_by',
				'updated_by' => 'updated_by',
				'deleted_by' => 'deleted_by',
				'admin' => 'tinyint',
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