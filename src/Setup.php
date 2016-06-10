<?php

declare(strict_types=1);

mb_internal_encoding('UTF-8');

date_default_timezone_set('UTC');

header_remove('X-Powered-By');


require_once 'Env.php';
require_once 'HttpStatus.php';
require_once 'Response.php';
require_once 'Database.php';


// Determine the current environment

Env::set(Env::TEST);


// Router configuration

Router::setPathPrefix('/api');


// Database configuration

Database::config([
	'type' => 'mysql',
	'host' => 'localhost',
	'name' => 'test',
	'user' => 'root',
	'pass' => 'root'
], [
	PDO::ATTR_PERSISTENT => true,
	PDO::ATTR_CASE => PDO::CASE_LOWER,
	PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);


// Error firing

if (Env::isProd()) {
	error_reporting(0);
	ini_set('display_errors', '0');
} else {
	error_reporting(E_ALL | E_STRICT); // TODO: avoid NOTICEs
	ini_set('display_errors', '1');
}


// Error handling

set_exception_handler(function (Throwable $e) {
	$data = [
		'title' => 'Unexpected exception',
		'detail' => $e->getMessage ?: ''
	];

	if (!Env::isProd())
		$data['debug'] = [
			'exception' => get_class($e) . ' (' . $e->getCode() . ')',
			'file' => $e->getFile() . ':' . $e->getLine(),
			'trace' => $e->getTrace() // getTraceAsString()
		];

	(new Response(HttpStatus::InternalServerError, [], $data))->send();
});

set_error_handler(function (
	int $errno,
	string $errstr,
	string $errfile,
	int $errline,
	array $errcontext
) {
	$data = [
		'title' => 'Unexpected error',
		'detail' => $errstr ?: ''
	];

	if (!Env::isProd())
		$data['debug'] = [
			'error' => $errno,
			'file' => $errfile . ':' . $errline,
			'context' => $errcontext
		];

	(new Response(HttpStatus::InternalServerError, [], $data))->send();
});
