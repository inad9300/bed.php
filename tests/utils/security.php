<?php

require_once '../../src/utils/security/hash.php';

use namespace \utils\security;

class SecurityTest extends PHPUnit\Framework\TestCase {

	function testHash() {
		$this->assertNotEquals(hash('abc'), 'abc');
		$this->assertTrue(strlen(hash('abc')) > 8); // IDEA equals something? (does it depend on $cost parameter in a predictable way?)
	}

	function testVerify() {
		for ($i = 0; $i < 1024; ++$i)
			$this->assertTrue(verify('abc', hash('abc')));

		for ($i = 0; $i < 1024; ++$i)
			$this->assertFalse(verify('abz', hash('abc')));
	}
}
