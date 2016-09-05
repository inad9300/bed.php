<?php

declare(strict_types=1);

require_once '../../src/utils/strings/cut.php';

use namespace \utils\strings;

class StringsTest extends PHPUnit\Framework\TestCase {

	function testCut() {
		$this->assertEquals('a', cut('a', 3));
		$this->assertEquals('ab', cut('ab', 3));
		$this->assertEquals('abc', cut('abc', 3));
		$this->assertEquals('...', cut('abcd', 3));
		$this->assertEquals('...', cut('abcde', 3));
		$this->assertEquals('a...', cut('abcdef', 4));
		$this->assertEquals('ab...', cut('abcdef', 5));
	}
}
