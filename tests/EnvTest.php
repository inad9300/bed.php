<?php

declare(strict_types=1);

require_once '../src/Env.php';

class EnvTest extends PHPUnit\Framework\TestCase {

	function testGetTestDefault() {
		$this->assertEquals(Env::get(), Env::TEST);
		$this->assertTrue(Env::isTest());
	}

	function testGetAfterSet() {
		Env::set(Env::PROD);
		$this->assertEquals(Env::get(), Env::PROD);
		$this->assertTrue(Env::isProd());
		$this->assertFalse(Env::isTest());
	}
}
