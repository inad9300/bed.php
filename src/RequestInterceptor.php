<?php

require_once 'Request.php';

interface RequestInterceptor {
	// Optionally, a Response may be returned
	public function handle(Request $req);
}
