<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#admin', 'url' => '/admin', 'verb' => 'GET'],
		['name' => 'page#bankverbindung', 'url' => '/bankverbindung', 'verb' => 'GET'],
		['name' => 'api#members', 'url' => '/api/members', 'verb' => 'GET'],
		['name' => 'api#users', 'url' => '/api/users', 'verb' => 'GET'],
		['name' => 'api#getMember', 'url' => '/api/member/{id}', 'verb' => 'GET'],
		['name' => 'api#updateMember', 'url' => '/api/member/{id}', 'verb' => 'PUT'],
		['name' => 'api#withdrawMandate', 'url' => '/api/member/{id}/withdraw', 'verb' => 'POST'],
		['name' => 'api#myMember', 'url' => '/api/my-member', 'verb' => 'GET'],
		['name' => 'api#assignUser', 'url' => '/api/assign', 'verb' => 'POST'],
		['name' => 'api#unassignUser', 'url' => '/api/unassign', 'verb' => 'POST'],
	],
];
