<?php

require_once 'Response.php';

/**
 * Interface to be implemented by the middleware that is meant to be run after
 * all the processing is done, right before the response reaches the user. An
 * example of such middleware may be one that adds certain HTTP headers to add
 * CORS support.
 */
interface ResponseInterceptor {
	public function handle(Response $res): Response;
}
