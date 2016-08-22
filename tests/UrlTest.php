<?php

require_once '../src/Url.php';

class UrlTest extends PHPUnit\Framework\TestCase {

	/**
     * @expectedException InvalidArgumentException
     */
	function _testWrongUrl() {
		// new Url('');
		// After a set of tries, it was impossible to provide a malformed URL,
		// as parse_url understands that term. See more information in
		// http://php.net/manual/en/function.parse-url.php
	}

	const TEST_URL = 'https://user:pass@www.google.com:80/some_path?some=query&something=%20weird#some_fragment';

	function testUrl() {
		$url = new Url(self::TEST_URL);
		$this->assertEquals($url->getFull(), self::TEST_URL);
		$this->assertEquals($url->getScheme(), 'https');
		$this->assertEquals($url->getHost(), 'www.google.com');
		$this->assertEquals($url->getPort(), '80');
		$this->assertEquals($url->getPath(), '/some_path');

		// The HTML entities remain intact
		$this->assertEquals($url->getQuery(), 'some=query&something=%20weird');

		// The HTML entities are resolved
		$this->assertEquals($url->getParam('some'), 'query');
		$this->assertEquals($url->getParam('something'), ' weird');
		$this->assertEquals($url->getParams(), [
			'some' => 'query',
			'something' => ' weird'
		]);

		$this->assertEquals($url->getFragment(), 'some_fragment');
		$this->assertEquals($url->getUser(), 'user');
		$this->assertEquals($url->getPass(), 'pass');
	}
}
