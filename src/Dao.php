<?php

declare(strict_types=1);

namespace bed;

require_once 'Database.php';
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
		// Allow overriding the default behaviour
		if ($table !== null)
			return $table;

		// Default table name to the class name, removing the "Dao" suffix if
		// present
		$className = strtolower(static::class);
		$daoPos = strpos($className, 'dao');
		if ($daoPos === false)
			$className = substr($className, 0, $daoPos);

		return $className;
	}

	protected function getIdName(): string {
		return 'id';
	}

	protected function findColumnTypes(array $colNames): array {
		return array_map(function ($colName) {
			return $this->columns[$colName];
		}, $colNames);
	}

	protected function buildTypedArray(array $colNames, array $values): array {
		$typedArray = [];
		foreach ($colNames as $idx => $colName)
			$typedArray[] = [ $this->columns[$colName] => $values[$idx] ];

		return $typedArray;
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
			\bed\utils\sql\createPlaceholders(count($ids)) . ')';

		return $this->db->run(
			'select ' . implode(', ', $this->columnNames)
			. ' from ' . $this->tableName . $whereClause,
			$ids,
			$this->columnTypes
		);
	}

	public function deleteOne(int $id): int {
		return $this->db->run(
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
			\bed\utils\sql\createPlaceholders(count($ids)) . ')';

		return $this->db->run(
			'delete from ' . $this->tableName . $whereClause,
			$ids
		);
	}

	public function insertOne(array $data): int {
		$colNames = array_keys($data);

		return $this->db->run(
			'insert into ' . $this->tableName
			. '(' . implode(', ', $colNames) . ') values (' .
				\bed\utils\sql\createPlaceholders(count($colNames)) . ')',
			$this->buildTypedArray($colNames, array_values($data))
		);
	}

	public function insertMany(array $data): int {
		if (count($data) === 0)
			return 0;

		// Take the keys of the first element, otherwise the query would need
		// to be prepared many times as opposed to only once. For insertions of
		// different elements, use the insertOne() function in a loop instead
		$colNames = array_keys($data[0]);

		return $this->db->run(
			'insert into ' . $this->tableName
			. '(' . implode(', ', $colNames) . ') values (' .
				\bed\utils\sql\createPlaceholders(count($colNames)) . ')',
			array_map(function ($item) {
				return $this->buildTypedArray($colNames, array_values($item));
			}, $data)
		);
	}

	public function updateOne(array $data): int {
		// Make sure that the id is the last parameter
		$id = $data[$this->idName];
		if (!$id)
			throw new \InvalidArgumentException('Primary key is missing');

		unset($data[$this->idName]);
		$values = array_values($data);
		$values[] = $id;

		$colNames = array_keys($data);

		return $this->db->run(
			'update ' . $this->tableName
			. ' set ' . \bed\utils\sql\buildUpdateBody($colNames)
			. ' where ' . $this->idName . ' = ?',
			$this->buildTypedArray($colNames, $values)
		);
	}

	public function updateMany(array $data): int {
		if (count($data) === 0)
			return 0;

		// Take the keys of the first element, otherwise the query would need
		// to be prepared many times as opposed to only once. For insertions of
		// different elements, use the updateOne() function in a loop
		$colNames = array_keys($data[0]);

		return $this->db->run(
			'update ' . $this->tableName
			. ' set ' . \bed\utils\sql\buildUpdateBody($colNames)
			. ' where ' . $this->idName . ' = ?',
			array_map(function ($entity) {
				$id = $entity[$this->idName];
				if (!$id)
					throw new \InvalidArgumentException(
						'Primary key is missing');

				unset($entity[$this->idName]);
				$values = array_values($entity);
				$values[] = $id;

				return $this->buildTypedArray($colNames, $values);
			}, $data)
		);
	}
}
