<?php

declare(strict_types=1);

namespace bed\utils\sql;

function createPlaceholders(int $n): string {
	return str_repeat('?, ', $n - 1) . '?';
}
