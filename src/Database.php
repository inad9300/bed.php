<?php

require_once 'utils/arrays.php';

/**
 * The purpose of this class is to hold the different tyeps the columns can
 * take, as an equivalent alternative to PDO predefined constants
 * (http://php.net/manual/en/pdo.constants.php). This alternative is needed
 * in order to be able to mix in the same array regular elements and
 * associative elements, unambiguosly. Thus, the values of the types are given
 * as a string, as oppose to PDO constants, which are integers; and they change
 * each time they are accessed, so next time a different key is used
 *
 * In summary, this allows for a concise syntax when referring to parameters
 * in queries: [ 'x', 'y', Col::INT() => 2, Col::BOOL() => true, 'z' ]
 */
class Col {
	// The values are intentionally kept as one-length strings
	private static $_INT = 'i';
	private static $_STR = 's';
	private static $_LOB = 'l';
	private static $_BOOL = 'b';
	private static $_NULL = 'n';

	private static $_count = 0;

	public static function INT(): string {
		return self::_yieldNewKey(self::$_INT);
	}

	public static function STR(): string {
		return self::_yieldNewKey(self::$_STR);
	}

	public static function LOB(): string {
		return self::_yieldNewKey(self::$_LOB);
	}

	public static function BOOL(): string {
		return self::_yieldNewKey(self::$_BOOL);
	}

	public static function NULL(): string {
		return self::_yieldNewKey(self::$_NULL);
	}

	private static function _yieldNewKey(string $prefix): string {
		return $prefix . self::$_count++;
	}

	public static function getPdoType(string $colType): int {
		$realType = substr($colType, 0, 1);

		switch ($realType) {
		case self::$_INT: return PDO::PARAM_INT;
		case self::$_STR: return PDO::PARAM_STR;
		case self::$_LOB: return PDO::PARAM_LOB;
		case self::$_BOOL: return PDO::PARAM_BOOL;
		case self::$_NULL: return PDO::PARAM_NULL;
		default:
			throw new InvalidArgumentException('Wrong constant used as column type');
		}
	}
}

/**
 * Database abstraction.
 */
class Database {

	private static $_dbh;
	private static $_config;
	private static $_pdoOptions;
	private static $_lastStmt;

	/**
	 * Wrapper function to centralize all the database configuration.
	 */
	public static function config(array $config, array $pdoOptions = null) {
		// Validate required attributes
		foreach (['type', 'host', 'name', 'user', 'pass'] as $attr)
			if (empty($config[$attr]))
				throw new InvalidArgumentException(
					"The '$attr' is required to configure the database");

		self::$_config = $config;
		self::$_pdoOptions = $pdoOptions;
	}

	/**
	 * Obtain the instance representing the database connection (aka database
	 * handler).
	 */
	public static function get(): PDO {
		// Establish the connection only if the database instance is requested,
		// and only the first time
		if (!isset(self::$_dbh)) {
			$connStr = self::$_config['type']
					. ':host=' . self::$_config['host']
					. ';dbname=' . self::$_config['name']
					. ';charset=' . (self::$_config['charset'] ?? 'utf8mb4');

			self::$_dbh = new PDO(
				$connStr,
				self::$_config['user'],
				self::$_config['pass'],
				self::$_pdoOptions
			);
		}

		return self::$_dbh;
	}

	/**
	 * Return the last prepared/executed statement.
	 */
	public static function getStatement() {
		return self::$_lastStmt;
	}

	/**
	 * Simple wrapper for running database queries.
	 *
	 * @param $params List of parameter values. Optionally, an associative
	 * element can be provided, whose key would indicate the type (given as
	 * a Col function), and whose value would be the actual value of the
	 * parameter, overriding the auto-detection. Alternatively, an array of 
	 * arrays can be passed, resulting in the query being executed one time
	 * per item (which are arrays with the conditions earlier explained).
	 * @param $colTypes List of types in the form of a Col function, which may 
	 * be needed for SELECT statements.
	 */
	public static function run(
		string $q,
		array $params = [],
		array $colTypes = []
	) {
		$stmt = self::get()->prepare($q);
		self::$_lastStmt = $stmt;

		$isSelect = self::_isSelect($q);
		if (!$isSelect && !empty($colTypes))
			throw new InvalidArgumentException('There is no point on providing column types in non-SELECT statements');

		$manyParams = count($params) > 0 
					? isset($params[0]) && is_array($params[0]) && !utils\arrays\isAssoc($params[0])
					: false;

		if ($manyParams) {
			// TODO: redo according to the single-array case
		}

		$i = 1;
		foreach ($params as $key => $param) {
			$type = is_string($key) 
				? Col::getPdoType($key)
				: self::_pdoType($param);

			$stmt->bindParam($i++, $param, $type);
		}

		$stmt->execute();

		$data = [];
		$specialSelect = false;

		if ($isSelect && !empty($colTypes)) {
			$specialSelect = true;

			for ($i = 0, $l = count($colTypes); $i < $l; $i++) {
				$col = $stmt->getColumnMeta($i);
				$type = $colTypes[$i]
					? Col::getPdoType($colTypes[$i])
					: ($col['pdo_type'] ?: PDO::PARAM_STR);

				$stmt->bindColumn($i + 1, $data[$col['name']], $type);
			}
		}

		if ($specialSelect) {
			$stmt->fetchAll(PDO::FETCH_BOUND);
			return $data;
		}

		return $isSelect 
			? $stmt->fetchAll() 
			: $this->rowCount();
	}

	// Max. number of bytes a MySQL row may hold
	const _MYSQL_MAX_SIZE = 65535; 

	private static function _pdoType($thing): int {
		$phpType = gettype($thing);

		switch ($phpType) {
		case 'integer':
			return PDO::PARAM_INT;
		case 'boolean':
			return PDO::PARAM_BOOL;
		case 'resource':
			return PDO::PARAM_LOB;
		case 'NULL':
			return PDO::PARAM_NULL;
		case 'string':
			// If a large string is given, assume a BLOB. For that, the maximum
			// number of bytes MySQL can hold in a row is taken as reference
			if (strlen($thing) > self::_MYSQL_MAX_SIZE)
				return PDO::PARAM_LOB;

			return PDO::PARAM_STR;
		default:
			return PDO::PARAM_STR;
		}
	}

	// Default trim()'s mask plus left parentheses
	const _TRIM_SQL_MASK = "( \t\n\r\0\x0B";

	private static function _isSelect(string $stmt): bool {
		return 'SELECT' === strtoupper(
			substr(
				ltrim($stmt, self::_TRIM_SQL_MASK), 0, 6
			)
		);
	}
}

