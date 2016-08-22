<?php

namespace bed\utils\files;

function getMime(string $fileContent): string {
	return (new finfo(FILEINFO_MIME_TYPE))->buffer($fileContent);
}
