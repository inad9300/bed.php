<?php

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
	 * Return the last statement prepared / executed.
	 */
	public static function getStatement() {
		return self::$_lastStmt;
	}

	/**
	 * Simple wrapper, for simple queries. The goal is to cover most of the
	 * cases, while keeping the code small.
	 *
	 * Specifically, there is no good support for LOBs: the only operation
	 * permitted is insertion, and only when the given argument is of type
	 * 'resource'. More information in http://php.net/manual/en/pdo.lobs.php
	 */
	public static function run(
		string $q,
		array $params = [], 
		array $paramTypes = [] // PDO types, overriding auto-detection
		// array $colTypes = [] // For SELECT statements, may be different
	) {
		$stmt = self::get()->prepare($q);
		self::$_lastStmt = $stmt;

		$isSelect = self::_isSelect($q);
		$manyParams = count($params) > 0 
					? is_array($params[0]) && !Utils\Arrays\isAssoc($params[0])
					: false;

		if ($manyParams) {
			$results = [];
			foreach ($params as $args) {
				foreach ($args as $i => $arg)
					$stmt->bindParam(
						$i,
						$arg,
						$paramTypes[$i] ?? self::_pdoType($arg)
					);
				
				$stmt->execute();
				$results[] = $isSelect
							? $stmt->fetchAll() 
							: $stmt->rowCount();
			}
			return $results;
		}

		foreach ($params as $i => $param)
			$stmt->bindParam(
				$i,
				$param,
				$paramTypes[$i] ?? self::_pdoType($param)
			);

		$stmt->execute();
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
		}
		return PDO::PARAM_STR;
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

