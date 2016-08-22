<?php

require_once '../../src/utils/arrays/isAssoc.php';
require_once '../../src/utils/arrays/pluck.php';
require_once '../../src/utils/arrays/first.php';
require_once '../../src/utils/arrays/last.php';

use namespace \utils\arrays;

class ArraysTest extends PHPUnit\Framework\TestCase {

	function testIsArray() {
		$this->assertTrue(isAssoc([]));
		$this->assertTrue(isAssoc(['a' => 1]));

		$this->assertFalse(isAssoc([1, 2, 3]));
	}

	function testPluck() {
		$this->assertEquals([1, 3], pluck([
			['a' => 1, 'b' => 2],
			['a' => 3, 'b' => 4]
		], 'a'));
	}

	function testFirst() {
		$this->assertEquals(null, first([]));
		$this->assertEquals(3, first([3, 2, 1]));
	}

	function testLast() {
		$this->assertEquals(null, last([]));
		$this->assertEquals(1, last([3, 2, 1]));
	}
}
