<?php

declare(strict_types=1);

namespace bed\\utils\arrays;

function last(array $arr) {
	$len = count($arr);

	if ($len === 0)
		return null;

	return $arr[$len - 1];
}
