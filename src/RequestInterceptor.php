<?php

declare(strict_types=1);

namespace bed;

require_once 'Interceptor.php';

/**
 * Interface to be implemented by the middleware that is meant to be run before
 * the request is processed by the application. An example of such middleware
 * may serve to add an authentication layer.
 */
interface RequestInterceptor extends Interceptor {}
