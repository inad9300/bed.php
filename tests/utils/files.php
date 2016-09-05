<?php

declare(strict_types=1);

require_once '../../src/utils/files/getMime.php';

use namespace \utils\files;

class FilesTest extends PHPUnit\Framework\TestCase {

	function testGetMime() {
		$this->assertEquals('image/jpeg', ''); // TODO
	}
}
