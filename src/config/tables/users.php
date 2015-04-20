<?php

return [
	'keep_clean',
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
		'token' => [
			'type' => 'string',
			'hidden',
		],
		'last_login' => [
			'type' => 'datetime',
			'hidden',
		],
		'permissions', 
		'updated_at',
		'updated_by',
		'deleted_at',
	],
];