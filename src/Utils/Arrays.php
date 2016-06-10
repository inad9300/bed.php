<?php

namespace Utils\Arrays;

function isAssoc(array $arr): bool {
	return is_array($arr)
		&& array_keys($arr) !== range(0, count($arr) - 1);
}
