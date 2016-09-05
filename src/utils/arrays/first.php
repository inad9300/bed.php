<?php

declare(strict_types=1);

namespace bed\\utils\arrays;

function first(array $arr) {
	return $arr[0] ?? null;
}
