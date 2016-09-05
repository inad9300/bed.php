<?php

declare(strict_types=1);

namespace bed\utils\strings;

function cut(string $text, int $limit, string $tail = '...'): string {
	if (!$text)
		return '';

	if (mb_strlen($text) > $limit)
		return mb_substr($text, 0, $limit - mb_strlen($tail)) . $tail;

	return $text;
}
