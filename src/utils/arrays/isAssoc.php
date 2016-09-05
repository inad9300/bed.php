<?php

declare(strict_types=1);

namespace bed\utils\arrays;

function isAssoc(array $arr): bool {
	return is_array($arr)
		&& array_keys($arr) !== range(0, count($arr) - 1);
}
