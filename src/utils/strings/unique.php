<?php

namespace bed\utils\strings;

function unique(): string {
	return md5(uniqid(rand(), true));
}
