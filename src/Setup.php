<?php

declare(strict_types=1);

mb_internal_encoding('UTF-8');

date_default_timezone_set('UTC');

header_remove('X-Powered-By');


require_once 'Environment.php';
require_once 'ErrorResponse.php';
require_once 'HttpStatus.php';
require_once 'Response.php';


Environment::set(Environment::DEV);

Router::setBaseUrl('http://localhost/api/'); // Needed[ here]?
Router::setUploadPrefix('/upload');          // Needed[ here]?

ErrorResponse::setBaseUrl('http://localhost/docs/errors/');


if (!Environment::isProd()) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}


set_exception_handler(function (Throwable $e) {
    $msg = Environment::isProd() ? '' : $e->getMessage();
    (new ErrorResponse(HttpStatus::InternalServerError, 'Uncaught exception', $msg))->send();
});

set_error_handler(function (int $errno, string $errstr) {
    if ($errno === E_ERROR || 
        $errno === E_USER_ERROR) {
        $msg = Environment::isProd() ? '' : $errstr;
        (new ErrorResponse(HttpStatus::InternalServerError, 'Unexpected error', $msg))->send();
    }
});


// TODO: test: throw new Exception('Error Processing Request', 1);