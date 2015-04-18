<?php

return [
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
];