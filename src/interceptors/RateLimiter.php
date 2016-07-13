<?php

require_once '../Response.php';
require_once '../HttpStatus.php';
require_once '../RequestInterceptor.php';
require_once '../Router.php';

/**
 * Implement this interface to define a way to identify the clients to base
 * the rate limiter on.
 */
interface RateLimitStrategy {
	public function getClientUniqueId();
}

/**
 * Identifies the client by the IP used by it.
 */
class RateLimitIp implements RateLimitStrategy {
	public function getClientId() {
		if (!isset($_SERVER['REMOTE_ADDR']))
			throw new RuntimeException('A request has been received without a source IP address');
		
		return $_SERVER['REMOTE_ADDR'];
	}
}

/**
 * Way to determine how many requests a certain client (IP) may perform in one
 * minute.
 */
class RateLimit implements RequestInterceptor {
	const TIME_SUFF = '_rate_limit_time';
	const TIMES_SUFF = '_rate_limit_times';

	const RATE_LIMIT = 60;
	const WINDOW = 60; // In seconds

	private static $_timeKey;
	private static $_timesKey;
	private static $_clientId;
	private static $_endPoint;
	private static $_idGenerator;

	public static function setStrategy(RateLimitStrategy s) {
		self::$_idGenerator = s;
	}

	public function handle(Request $req, Response $res): Response {
		// Default client identification by IP
		if (empty(self::$_idGenerator))
			self::$_idGenerator = new RateLimitIp();

		self::$_endPoint = Router::current();
		self::$_clientId = self::$_idGenerator->getClientId();

		$keyPrefix = self::$_clientId . '@' . self::$_endPoint;
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
}

