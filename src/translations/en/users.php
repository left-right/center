<?php

return [	
	'title' => 'Users',
	'help' => [
		'index' => 'A list of all users in the system.',
		'create' => 'If the user has permissions, an email will be sent inviting them to the system.',
		'edit' => 'Granting edit permissions to users allows the user to edit their own permissions.',
	],
	'fields' => [
		'name' => 'Name',
		'last_login' => 'Last Login',
		'email' => 'Email',
		'updated_at' => 'Updated',
		'permissions' => 'Permissions',
	],
];	