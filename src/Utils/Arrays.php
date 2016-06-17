<?php

namespace Utils\Arrays;

function isAssoc(array $arr): bool {
	return is_array($arr)
		&& array_keys($arr) !== range(0, count($arr) - 1);
}

function pluck(array $arr, string $prop): array {
	$result = [];

	foreach ($arr as $obj)
		$result[] = $obj[$prop] ?? null;

	return $result;
}
