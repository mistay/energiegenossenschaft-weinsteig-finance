<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#admin', 'url' => '/admin', 'verb' => 'GET'],
		['name' => 'api#members', 'url' => '/api/members', 'verb' => 'GET'],
		['name' => 'api#users', 'url' => '/api/users', 'verb' => 'GET'],
		['name' => 'api#assignUser', 'url' => '/api/assign', 'verb' => 'POST'],
		['name' => 'api#unassignUser', 'url' => '/api/unassign', 'verb' => 'POST'],
	],
];
