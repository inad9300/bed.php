<?php

declare(strict_types=1);

namespace bed\utils\arrays;

function pluck(array $arr, string $prop): array {
	$result = [];

	foreach ($arr as $obj)
		$result[] = $obj[$prop] ?? null;

	return $result;
}
