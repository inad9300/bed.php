<?php

declare(strict_types=1);

require_once '../../src/utils/security/hash.php';

use namespace \utils\security;

class SecurityTest extends PHPUnit\Framework\TestCase {

	function testHash() {
		$this->assertNotEquals(hash('abc'), 'abc');
		$this->assertTrue(strlen(hash('abc')) > 8);
	}

	function testVerify() {
		for ($i = 0; $i < 1024; ++$i)
			$this->assertTrue(verify('abc', hash('abc')));

		for ($i = 0; $i < 1024; ++$i)
			$this->assertFalse(verify('abz', hash('abc')));
	}
}
