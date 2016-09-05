<?php

declare(strict_types=1);

namespace bed;

/**
 * The purpose of this class is to hold the different types the columns can
 * take, as an equivalent alternative to PDO predefined constants
 * (http://php.net/manual/en/pdo.constants.php). This alternative is needed
 * in order to be able to mix in the same array regular elements and
 * associative elements, unambiguosly. Thus, the values of the types are given
 * as a string, as oppose to PDO constants, which are integers; and they change
 * each time they are accessed, so next time a different key is used.
 *
 * In summary, this allows for a concise syntax when referring to parameters
 * in queries: [ 'x', 'y', Col::INT() => 2, Col::BOOL() => true, 'z' ]
 */
class Col {

	// The values are intentionally kept as one-length strings
	protected static $INT = 'i';
	protected static $STR = 's';
	protected static $LOB = 'l';
	protected static $BOOL = 'b';
	protected static $NULL = 'n';

	protected static $count = 0;

	public static function INT(): string {
		return self::_yieldNewKey(self::$INT);
	}

	public static function STR(): string {
		return self::_yieldNewKey(self::$STR);
	}

	public static function LOB(): string {
		return self::_yieldNewKey(self::$LOB);
	}

	public static function BOOL(): string {
		return self::_yieldNewKey(self::$BOOL);
	}

	public static function NULL(): string {
		return self::_yieldNewKey(self::$NULL);
	}

	protected static function _yieldNewKey(string $prefix): string {
		return $prefix . self::$count++;
	}

	public static function getPdoType(string $colType): int {
		$realType = $colType[0];

		switch ($realType) {
		case self::$INT: return \PDO::PARAM_INT;
		case self::$STR: return \PDO::PARAM_STR;
		case self::$LOB: return \PDO::PARAM_LOB;
		case self::$BOOL: return \PDO::PARAM_BOOL;
		case self::$NULL: return \PDO::PARAM_NULL;
		default:
			throw new \InvalidArgumentException(
				'Wrong constant used as column type');
		}
	}
}
