<?php

namespace bed;

require_once '../RequestInterceptor.php';
require_once '../Request.php';
require_once '../Response.php';
require_once '../Router.php';
require_once '../HttpStatus.php';

require_once 'RateLimitStrategy.php';
require_once 'RateLimitIp.php';

/**
 * Way to determine how many requests a certain client may perform per unit of
 * time.
 */
class RateLimit implements RequestInterceptor {

	const RATE_LIMIT = 60;
	const RATE_WINDOW_S = 60; // In seconds

	protected $router;
	protected $idGenerator;

	protected static $time_suffix = '_rate_limit_time';
	protected static $times_suffix = '_rate_limit_times';

	public function __construct(Router $r, RateLimitStrategy $s = null) {
		$this->router = $r;
		$this->idGenerator = $s;
	}

	public function handle(Request $req, Response $res): Response {
		// Default client identification by IP
		if (!$this->idGenerator)
			$this->idGenerator = new RateLimitIp;

		$endPoint = $this->router->getCurrent();
		$clientId = $this->idGenerator->getClientId();

		$keyPrefix = $clientId . '@' . $endPoint;
		$timeKey = $keyPrefix . self::$time_suffix;
		$timesKey = $keyPrefix . self::$times_suffix;

		$time = apc_exists($timeKey) ? apc_fetch($timeKey) : time();
		$times = apc_exists($timesKey) ? apc_fetch($timesKey) : 1;

		$res->addHeader('X-Rate-Limit-Limit', self::RATE_LIMIT);

		// If the time window passed since the last request was made, restart
		// the counting
		if (time() - $time > self::RATE_WINDOW_S) {
			apc_store($timesKey, 1);
			apc_store($timeKey, time());

			$res->addHeader('X-Rate-Limit-Remaining', 59);
			$res->addHeader('Retry-After', 0);

			return $res;
		}

		if ($times > self::RATE_LIMIT)
			return $res->build(HttpStatus::TooManyRequests, [
				'X-Rate-Limit-Remaining' => 0,
				'Retry-After' => self:RATE_WINDOW_S - (time() - $time)
			], [
				'title' => 'Rate limit exceeded',
				'detail' => 'You already did more than ' . self::RATE_LIMIT .
							' requests in the last minute. Please, wait until the next time window is opened.'
			]);
		else
			apc_store($timesKey, ++$times);

		$res->addHeader('X-Rate-Limit-Remaining', self::RATE_LIMIT - $times);

		// Should be 0 any time there are remaining requests
		$res->addHeader('Retry-After', time() - $time);

		return $res;
	}
}
