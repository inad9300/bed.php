<?php

require_once 'Response.php';

interface ResponseInterceptor {
	public function handle(Response $res): Response;
}
