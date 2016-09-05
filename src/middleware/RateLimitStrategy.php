<?php

declare(strict_types=1);

namespace bed;

/**
 * Implement this interface to define a way to identify the clients to base
 * the rate limiter on.
 */
interface RateLimitStrategy {

	public function getClientUniqueId();
}
