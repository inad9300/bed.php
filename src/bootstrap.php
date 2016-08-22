<?php

// Force the types to be present in function signatures
// FIXME does not apply to all the files bellow the declaration...
declare(strict_types=1);

namespace bed;

// Files required for the setup itself
require_once 'Env.php';
require_once 'Response.php';
require_once 'HttpStatus.php';

/**
 * Example of function that defines a setup process common to all the
 * application. May be take as a template, or used as-is. Suggestions on new
 * things to include or ways to improve it are welcome :)
 */
function bootstrap(int $env = Env::PROD) {

	// Set the encoding of the mb_* functions
	mb_internal_encoding('UTF-8');

	// Set the same timezone as the one used by the database
	date_default_timezone_set('UTC');

	// Get rid of PHP's default custom header
	header_remove('X-Powered-By');

	// Determine the current environment
	Env::set($env);

	// Control which errors are fired depending on the environment
	if (Env::isProd()) {
		error_reporting(0);
		ini_set('display_errors', '0');
	} else {
		error_reporting(E_ALL | E_STRICT); // TODO avoid E_NOTICEs
		ini_set('display_errors', '1');
	}

	// Handling errors from exceptions
	set_exception_handler(function (\Throwable $e) {
		$data = [
			'title' => 'Unexpected exception',
			'detail' => $e->getMessage() ?: ''
		];

		if (Env::isDev())
			$data['debug'] = [
				'exception' => get_class($e) . ' (' . $e->getCode() . ')',
				'file' => $e->getFile() . ':' . $e->getLine(),
				'trace' => $e->getTrace()
			];

		(new Response(HttpStatus::InternalServerError, [], $data))->send();
	});

	// Handling errors from trigger_error and the alike
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

		if (Env::isDev())
			$data['debug'] = [
				'error' => $errno,
				'file' => $errfile . ':' . $errline,
				'context' => $errcontext
			];

		(new Response(HttpStatus::InternalServerError, [], $data))->send();
	});
}
