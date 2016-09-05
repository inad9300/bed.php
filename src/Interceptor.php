<?php

declare(strict_types=1);

namespace bed;

require_once 'Request.php';
require_once 'Response.php';

/**
 * Basic shape of an interceptor or middleware.
 */
interface Interceptor {

	public function handle(Request $req, Response $res): Response;
}
