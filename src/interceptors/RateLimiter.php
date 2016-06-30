<?php

// FIXME: build as interceptor / middleware, so that the headers can be
// attached to all requests, and not only if the time window has passed
// TODO: support auth*
// TODO: make it work for specific end-points - remember to support 
// placeholders
// IDEA: configuration option to decide if the limit is based on IP or on the
// authenticated user

require_once 'Response.php';
require_once 'HttpStatus.php';
require_once '../RequestInterceptor.php';

/**
 * Way to determine how many requests a certain client (IP) may perform in one
 * minute.
 */
class RateLimiter implements RequestInterceptor {

	const BASE_KEY = '_rate_limit';
	const TIME_SUFF = '_time';
	const TIMES_SUFF = '_times';

	const RATE_LIMIT = 60;
	const WINDOW = 60; // In seconds

	// private static $_scope;
	private static $_timeKey;
	private static $_timesKey;
	private static $_clientIp;

	public function handle(Request $req, Response $res): Response {
		self::$_clientIp = self::getClientIp();

		$keyPrefix = $scope . self::BASE_KEY . self::$_clientIp;
		self::$_timeKey = $keyPrefix . self::TIME_SUFF;
		self::$_timesKey = $keyPrefix . self::TIMES_SUFF;

		$time = apc_exists(self::$_timeKey)
				? apc_fetch(self::$_timeKey)
				: time();

		$times = apc_exists(self::$_timesKey)
				? apc_fetch(self::$_timesKey)
				: 1;
		
		$res->addHeader('X-Rate-Limit-Limit', self::RATE_LIMIT);

		// If the time window passed since the last request was made, restart
		// the counting
		if (time() - $time > self::WINDOW) {
			apc_store(self::$_timesKey, 1);
			apc_store(self::$_timeKey, time());

			$res->addHeader('X-Rate-Limit-Remaining', 59);
			$res->addHeader('Retry-After', 0);

			return $res;
		}

		if ($times > self::RATE_LIMIT) {
			$res->setStatus(HttpStatus::TooManyRequests);
			$res->addHeaders([
				'X-Rate-Limit-Remaining' => 0,
				'Retry-After' => self:WINDOW - (time() - $time))
			]);
			$res->setPayload([
				'title' => 'Rate limit exceeded',
				'detail' => 'You already did more than ' . self::RATE_LIMIT .
							' requests in the last minute. Please, wait until the next time window is opened.'
			]);
			$res->send();
		} else {
			$times += 1;
			apc_store(self::$_timesKey, $times);
		}

		$res->addHeader('X-Rate-Limit-Remaining', self::RATE_LIMIT - $times);
		$res->addHeader('Retry-After', time() - $time); // Should be 0 any time there are remaining requests

		return $res;
	}

	public static function getClientIp(): string {
		if (!isset($_SERVER['REMOTE_ADDR']))
			throw new RuntimeException('A request has been received without a source IP address');
		
		return $_SERVER['REMOTE_ADDR'];
	}
}

