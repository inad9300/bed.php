<?php

require_once 'Request.php';

/**
 * Interface to be implemented by the middleware that is meant to be run before
 * the request is processed by the application. An example of such middleware
 * may serve to add an authentication layer.
 */
interface RequestInterceptor {
	// Optionally, a Response may be returned
	public function handle(Request $req);
}
