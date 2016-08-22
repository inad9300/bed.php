<?php

namespace bed;

require_once 'Col.php';
require_once 'utils/arrays/isAssoc.php';

/**
 * Database abstraction.
 */
class Database {

	protected $dbh;
	protected $config;
	protected $pdoOptions;
	protected $lastStmt;

	/**
	 * Accepts a readable array with the properties needed to build the
	 * connection string. Plus, an array with PDO options for fine-grain
	 * configuration.
	 */
	public function __construct(array $config, array $pdoOptions = null) {
		// Check presence of required attributes
		foreach (['type', 'host', 'name', 'user', 'pass'] as $attr)
			if (!$config[$attr])
				throw new \InvalidArgumentException(
					"The '$attr' is required to configure the database");

		$this->config = $config;
		$this->pdoOptions = $pdoOptions;
	}

	/**
	 * Obtain the instance representing the database connection (AKA database
	 * handler).
	 */
	public function get(): \PDO {
		// Establish the connection only if the database instance is requested,
		// and only the first time
		if (!$this->dbh) {
			$connStr = $this->config['type']
					. ':host=' . $this->config['host']
					. ';dbname=' . $this->config['name']
					. ';charset=' . ($this->config['charset'] ?? 'utf8mb4');

			$this->dbh = new \PDO(
				$connStr,
				$this->config['user'],
				$this->config['pass'],
				$this->pdoOptions
			);
		}
		return $this->dbh;
	}

	/**
	 * Return the last run statement.
	 */
	public function getLastStatement() {
		return $this->lastStmt;
	}

	/**
	 * TODO
	 */
	public function startTransaction() {}

	/**
	 * TODO
	 */
	public function endTransaction() {}

	/**
	 * Simple wrapper for running database queries.
	 *
	 * @param $q Database query to be run, as a string. Alternatively, an
	 * already-prepared statement can be provided, so that the preparation
	 * can be done simply once.
	 * @param $params List of parameter values. Optionally, an associative
	 * element can be provided, whose key would indicate the type (given as
	 * one of Col's functions), and whose value would be the actual value of
	 * the parameter, overriding the auto-detection.
	 * @param $colTypes List of types in the form of a Col function, which may
	 * be needed for SELECT statements.
	 */
	public function run($q, array $params = [], array $colTypes = []) {
		$isSelect = $this->isSelect($q);
		if (!$isSelect && $colTypes)
			throw new \InvalidArgumentException(
				'There is no point on providing column types in non-SELECT statements');

		if (is_string($q))
			$stmt = $this->lastStmt = $this->get()->prepare($q);
		else if ($q instanceof \PDOStatement)
			$stmt = $this->lastStmt = $q;
		else
			throw new \InvalidArgumentException(
				'The first parameter must be either a query string or a prepared statement');

		$i = 1;
		foreach ($params as $key => $val)
			$stmt->bindParam(
				$i++,
				$val,
				is_string($key)
					? Col::getPdoType($key)
					: $this->getPdoType($val)
			);

		$stmt->execute();

		$colsCount = count($colTypes);
		if ($isSelect) {
			if ($colsCount === 0)
				return $stmt->fetchAll();

			$meta = [];
			$types = [];
			for ($i = 0; $i < $colsCount; ++$i) {
				// TODO test availability in different database engines
				$meta[] = $stmt->getColumnMeta($i);
				$types[] = $colTypes[$i]
					? Col::getPdoType($colTypes[$i])
					: ($meta[$i]['pdo_type'] ?: \PDO::PARAM_STR);

				// First binding, to avoid ending up with a null element
				$stmt->bindColumn(
					$i + 1,
					$res[$meta[$i]['name']],
					$types[$i]
				);
			}

			$data = [];

			// IDEA try to make it work with $stmt->fetchAll(\PDO::FETCH_BOUND);
			while ($stmt->fetch(\PDO::FETCH_BOUND)) {
				$data[] = $res;

				// Break the references, otherwise all the elements will point
				// to the same object, and therefore contain the same data
				$res = null;

				// Bind the next result, in case there is any more data
				for ($i = 0; $i < $colsCount; ++$i)
					$stmt->bindColumn(
						$i + 1,
						$res[$meta[$i]['name']],
						$types[$i]
					);
			}
			return $data;
		}

		return $this->rowCount();
	}

	// Upper limit for a variable to be considered a string, in bytes. For
	// that, the maximum number of bytes MySQL can fit in a row is taken a
	// reference
	const STRING_MAX_SIZE = 65535;

	protected function getPdoType($thing): int {
		$phpType = gettype($thing);

		switch ($phpType) {
		case 'integer': return \PDO::PARAM_INT;
		case 'boolean': return \PDO::PARAM_BOOL;
		case 'resource': return \PDO::PARAM_LOB;
		case 'NULL': return \PDO::PARAM_NULL;
		case 'string':
			// If a large string is given, assume a BLOB
			if (strlen($thing) > self::STRING_MAX_SIZE)
				return \PDO::PARAM_LOB;

			return \PDO::PARAM_STR;
		default:
			return \PDO::PARAM_STR;
		}
	}

	// Default trim()'s mask plus left parentheses
	const TRIM_SQL_MASK = "( \t\n\r\0\x0B";

	protected function isSelect(string $stmt): bool {
		return 'select' === strtolower(
			substr(
				ltrim($stmt, self::TRIM_SQL_MASK), 0, 6
			)
		);
	}
}
