<?php

return [
	'keep_clean',
	'hidden',
	'fields' => [
		'row_id' => 'integer',
		'table' => [
			'type' => 'string',
			'required',
		],
		'field' => [
			'type' => 'string',
			'required',
		],
		'url' => [
			'type' => 'string',
			'required',
		],
		'width' => 'integer',
		'height' => 'integer',
		'size' => [
			'type' => 'integer',
			'required',
		],
		'created_at',
		'created_by',
		'precedence'=>'integer',
	],
];