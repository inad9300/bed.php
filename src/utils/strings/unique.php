<?php

declare(strict_types=1);

namespace bed\utils\strings;

function unique(): string {
	return md5(uniqid(rand(), true));
}
