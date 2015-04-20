<?php

return [
	'keep_clean',
	'hidden',
	'fields' =>	[
		'user_id' => [
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