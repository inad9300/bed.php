<?php

namespace bed;

/**
 * Globally control the current environment of execution. Request it later to
 * conditionally control the execution flow for each case.
 */
class Env {

	const DEV = 0;
	const PROD = 1;

	protected static $current = self::DEV;

	public static function set(int $env) {
		if ($env !== self::DEV && $env !== self::PROD)
			throw new \InvalidArgumentException(
				'The environment cannot hold such value');

		self::$current = $env;
	}

	public static function get(): int {
		return self::$current;
	}

	public static function isDev(): bool {
		return self::$current === self::DEV;
	}

	public static function isProd(): bool {
		return self::$current === self::PROD;
	}
}
