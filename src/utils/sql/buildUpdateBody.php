<?php

declare(strict_types=1);

namespace bed\utils\sql;

function buildUpdateBody(array $cols): string {
	$parts = [];

	foreach ($cols as $col)
		$parts[] = $col . ' = ?';

	return implode(', ', $parts);
}
