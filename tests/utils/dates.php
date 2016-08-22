<?php

require_once '../../src/utils/dates/isIso8601.php';

use namespace \utils\dates;

class DatesTest extends PHPUnit\Framework\TestCase {

	function testIsIso8601() {
		$this->assertTrue(isIso8601('2016-01-01'));
		$this->assertTrue(isIso8601('2016-01-01T22:00:00'));
		$this->assertTrue(isIso8601('2016-01-01T22:00:00Z'));
		$this->assertTrue(isIso8601('2016-01-01T22:00:00.000Z'));
		$this->assertTrue(isIso8601('2016-01-01T22:00:00+02:00'));
		$this->assertTrue(isIso8601('2016-01-01T22:00:00.000+02:00'));

		$this->assertFalse(isIso8601('01-01-2016'));
		$this->assertFalse(isIso8601('2016-88-01T22:00:00'));
		$this->assertFalse(isIso8601('2016-01-88T22:00:00'));
		$this->assertFalse(isIso8601('2016-01-01T88:00:00'));
	}
}
