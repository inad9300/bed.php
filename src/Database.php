<?php

class Database {

	private static $_dbh;
	private static $_config;
	private static $_pdoOptions;


	public static function config(array $config, array $pdoOptions = null) {
		// Validate required attributes
		foreach (['type', 'host', 'name', 'user', 'pass'] as $attr)
			if (empty($config[$attr]))
				throw new InvalidArgumentException(
					"The '$attr' attribute is required to configure the database");

		self::$_config = $config;
		self::$_pdoOptions = $pdoOptions;
	}

	public static function get(): PDO {
		// Establish the connection only if the database instance is requested,
		// and only the first time
		if (!isset(self::$_dbh)) {
			$connStr = self::$_config['type']
					. ':host=' . self::$_config['host']
					. ';dbname=' . self::$_config['name']
					. ';charset=' . self::$_config['charset'] ?: 'utf8mb4';

			self::$_dbh = new PDO($connStr, self::$_config['user'], self::$_config['pass'], self::$_pdoOptions);
		}

		return self::$_dbh;
	}

	/**
	 * Simple wrapper, for simple queries. The goal is to cover most of the
	 * cases, while keeping the code small.
	 *
	 * Specifically, there is no good support for LOBs: the only operation
	 * permitted is insertion, and only when the given argument is of type
	 * 'resource'. More information in http://php.net/manual/en/pdo.lobs.php
	 */
	public static function run(string $q, array $params = []) {
		$stmt = Database::get()->prepare($q);

		for ($i = 0, $c = count($params); $i < $c; ++$i)
			$stmt->bindParam($i, $params[$i], self::_pdoType(gettype($params[$i])));

		if (($stmt->execute()) === false)
			throw new RuntimeException('Query execution failed: ' . ($stmt->errorInfo())[2]);

		if (self::_isSelect($q))
			return $stmt->fetchAll();

		return $stmt->rowCount();
	}

	private static function _pdoType(string $phpType): int {
		switch ($phpType) {
		case 'integer':
			return PDO::PARAM_INT;
		case 'boolean':
			return PDO::PARAM_BOOL;
		case 'resource':
			return PDO::PARAM_LOB;
		case 'NULL':
			return PDO::PARAM_NULL;
		}
		return PDO::PARAM_STR;
	}

	// Default trim()'s mask plus left parentheses
	private const _TRIM_SQL_MASK = '( \t\n\r\0\x0B';

	private static function _isSelect(strign $stmt): bool {
		return 'SELECT' === strtoupper(
			substr(
				ltrim($stmt, Database::_TRIM_SQL_MASK), 0, 6
			)
		);
	}

}
