<?php

declare(strict_types=1);

namespace bed\utils\arrays;

/**
 * Check if all elements in an array verify a certain condition, e.g.
 * \utils\arrays\allVerify(is_numeric, [1, 2, 3]) === true
 */
function allVerify(callable $condition, array $arr): bool {
	// return count($arr) === count(array_filter($arr, $condition));
	// vs
	// return 0 === count(array_filter($arr, function ($item): bool {
	// 	return !$condition($item);
	// }));
	// vs
	foreach ($arr as $item)
		if ($condition($item) !== true)
			return false;

	return true;
}
