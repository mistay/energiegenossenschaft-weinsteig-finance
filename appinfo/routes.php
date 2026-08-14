<?php

declare(strict_types=1);

return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'page#admin', 'url' => '/admin', 'verb' => 'GET'],
		['name' => 'page#bankverbindung', 'url' => '/bankverbindung', 'verb' => 'GET'],
		['name' => 'page#vorschreibungen', 'url' => '/vorschreibungen', 'verb' => 'GET'],
		['name' => 'page#zahlungen', 'url' => '/zahlungen', 'verb' => 'GET'],
		['name' => 'api#members', 'url' => '/api/members', 'verb' => 'GET'],
		['name' => 'api#users', 'url' => '/api/users', 'verb' => 'GET'],
		['name' => 'api#getMember', 'url' => '/api/member/{id}', 'verb' => 'GET'],
		['name' => 'api#updateMember', 'url' => '/api/member/{id}', 'verb' => 'PUT'],
		['name' => 'api#withdrawMandate', 'url' => '/api/member/{id}/withdraw', 'verb' => 'POST'],
		['name' => 'api#mandatePdf', 'url' => '/api/member/{id}/mandate-pdf', 'verb' => 'GET'],
		['name' => 'api#uploadSignedMandate', 'url' => '/api/member/{id}/mandate-signed', 'verb' => 'POST'],
		['name' => 'api#getSignedMandate', 'url' => '/api/member/{id}/mandate-signed', 'verb' => 'GET'],
		['name' => 'api#downloadSignedMandate', 'url' => '/api/download/{id}', 'verb' => 'GET'],
		['name' => 'api#myMember', 'url' => '/api/my-member', 'verb' => 'GET'],
		['name' => 'api#assignUser', 'url' => '/api/assign', 'verb' => 'POST'],
		['name' => 'api#unassignUser', 'url' => '/api/unassign', 'verb' => 'POST'],
		['name' => 'api#vorschreibungPdf', 'url' => '/api/vorschreibung/{id}/{month}', 'verb' => 'GET'],
		['name' => 'api#getVorschreibungen', 'url' => '/api/vorschreibungen', 'verb' => 'GET'],
		['name' => 'api#generateVorschreibungen', 'url' => '/api/vorschreibungen/{year}/{month}/generate', 'verb' => 'POST'],
		['name' => 'api#zahlungenImport', 'url' => '/api/zahlungen/import', 'verb' => 'POST'],
		['name' => 'api#zahlungenGetUnmatched', 'url' => '/api/zahlungen/unmatched', 'verb' => 'GET'],
		['name' => 'api#zahlungenAssign', 'url' => '/api/zahlungen/{zahlungId}/assign/{memberId}', 'verb' => 'POST'],
		['name' => 'api#zahlungenGet', 'url' => '/api/zahlungen', 'verb' => 'GET'],
	],
];
