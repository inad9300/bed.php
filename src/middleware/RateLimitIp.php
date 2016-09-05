<?php

declare(strict_types=1);

namespace bed;

require_once 'RateLimitStrategy.php';

/**
 * Identifies the client by the IP used by it.
 */
class RateLimitIp implements RateLimitStrategy {

	public function getClientId() {
		if (!$_SERVER['REMOTE_ADDR'])
			throw new \RuntimeException(
				'A request has been received without a source IP address');

		return $_SERVER['REMOTE_ADDR'];
	}
}
