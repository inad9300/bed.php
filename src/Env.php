<?php

/**
 * Globally control the current environment of execution. Request it later to
 * conditionally control the execution flow for each case.
 */
class Env {

	const TEST = 0;
	const PROD = 1;

	private static $_current = self::TEST;

	public static function set(int $env) {
		if ($env !== self::TEST &&
			$env !== self::PROD)
			throw new InvalidArgumentException(
				'The environment cannot hold such value');

		self::$_current = $env;
	}

	public static function get(): int {
		return self::$_current;
	}

	public static function isTest(): bool {
		return self::$_current === self::TEST;
	}

	public static function isProd(): bool {
		return self::$_current === self::PROD;
	}
}

