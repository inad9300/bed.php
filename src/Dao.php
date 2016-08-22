<?php

namespace bed;

require_once 'Database.php';
require_once 'utils/arrays/isAssoc.php';
require_once 'utils/sql/createPlaceholders.php';
require_once 'utils/sql/buildUpdateBody.php';

/**
 * Generic class with common database operations.
 */
class Dao {

	protected $db;

	/**
	 * Map from name to type as specified by the Col class.
	 */
	protected $columns;
	protected $columnNames;

	protected $tableName;
	protected $idName;

	public function __construct(
		Database $db,
		array $columns,
		string $table = null
	) {
		$this->db = $db;

		$this->columns = $columns;
		$this->columnNames = array_keys($columns);
		$this->columnTypes = array_values($columns);

		$this->tableName = $this->getTableName($table);
		$this->idName = $this->getIdName();
	}

	protected function getTableName(string $table = null): string {
		if ($table !== null)
			return $table;

		$lowerTable = strtolower($table);

		// TODO get after the name of the class - "dao", in lowercase
		// search for "php remove suffix"

		return '';
	}

	protected function getIdName(): string {
		return 'id';
	}

	protected function findColumnTypes(array $colNames): array {
		return array_map(function ($colName) {
			return $this->columns[$colName];
		}, $colNames);
	}

	public function exists(int $id): bool {
		return $this->db->run(
			'select count(*) as count from ' . $this->tableName
			. ' where ' . $this->idName . ' = ?',
			[Col::INT() => $id],
			[Col::INT()]
		)['count'] === 1;
	}

	public function findOne(int $id): array {
		$resultSet = $this->db->run(
			'select ' . implode(', ', $this->columnNames)
			. ' from ' . $this->tableName
			. ' where ' . $this->idName . ' = ?',
			[Col::INT() => $id],
			$this->columnTypes
		);
		return !$resultSet ? null : $resultSet[0];
	}

	public function findMany(array $ids = null): array {
		if ($ids !== null && count($ids) === 0)
			return [];

		$whereClause = $ids === null ? '' :
			' where ' . $this->idName . ' in (' .
			\utils\sql\createPlaceholders(count($ids)) . ')';

		return Database::run(
			'select ' . implode(', ', $this->columnNames)
			. ' from ' . $this->tableName . $whereClause,
			$ids,
			$this->columnTypes
		);
	}

	public function deleteOne(int $id): int {
		return Database::run(
			'delete from ' . $this->tableName
			. ' where ' . $this->idName . ' = ?',
			[Col::INT() => $id]
		);
	}

	public function deleteMany(array $ids = null): int {
		if ($ids !== null && count($ids) === 0)
			return 0;

		$whereClause = $ids === null ? '' :
			' where ' . $this->idName . ' in (' .
			\utils\sql\createPlaceholders(count($ids)) . ')';

		return Database::run(
			'delete from ' . $this->tableName . $whereClause,
			$ids
		);
	}

	// TODO include type information for the parameters
	public function insertOne(array $data): int {
		$colNames = array_keys($data);

		return Database::run(
			'insert into ' . $this->tableName
			. '(' . implode(', ', $colNames) . ') values (' .
				\utils\sql\createPlaceholders(count($colNames)) . ')',
			array_values($data)
		);
	}

	// TODO include type information for the parameters
	public function insertMany(array $data): int {
		if (count($data) === 0)
			return 0;

		// Take the keys of the first element, otherwise the query would need
		// to be prepared many times as opposed to only once. For insertions of
		// different elements, use the insertOne() function in a loop
		$colNames = array_keys($data[0]);

		return Database::run(
			'insert into ' . $this->tableName
			. '(' . implode(', ', $colNames) . ') values (' .
				\utils\sql\createPlaceholders(count($colNames)) . ')',
			array_map(function ($item) {
				return array_values($item);
			}, $data)
		);
	}

	// TODO include type information for the parameters
	public function updateOne(array $data): int {
		// Make sure that the id is the last parameter
		$id = $data[$this->idName];
		if (!$id)
			throw new \InvalidArgumentException('Primary key is missing');

		unset($data[$this->idName]);
		$params = array_values($data);
		$params[] = $id;

		$colNames = array_keys($data);

		return Database::run(
			'update ' . $this->tableName
			. ' set ' . \utils\sql\buildUpdateBody($colNames)
			. ' where ' . $this->idName . ' = ?',
			$params
		);
	}

	// TODO include type information for the parameters
	public function updateMany(array $data): int {
		if (count($data) === 0)
			return 0;

		// Take the keys of the first element, otherwise the query would need
		// to be prepared many times as opposed to only once. For insertions of
		// different elements, use the updateOne() function in a loop
		$colNames = array_keys($data[0]);

		return Database::run(
			'update ' . $this->tableName
			. ' set ' . \utils\sql\buildUpdateBody($colNames)
			. ' where ' . $this->idName . ' = ?',
			array_map(function ($entity) {
				$id = $entity[$this->idName];
				if (!$id)
					throw new \InvalidArgumentException(
						'Primary key is missing');

				unset($entity[$this->idName]);
				$values = array_values($entity);
				$values[] = $id;

				return $values;
			}, $data)
		);
	}
}
