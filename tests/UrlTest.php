<?php

require_once '../src/Url.php';

class UrlTest extends PHPUnit\Framework\TestCase {

	/**
     * @expectedException InvalidArgumentException
     */
	function _testWrongUrl() {
		// new Url('');
		// After a few tries, it was impossible to provide a malformed URL, as
		// parse_url (http://php.net/manual/en/function.parse-url.php)
		// understands that term
	}

	const TEST_URL = 'https://user:pass@www.google.com:80/some_path?some=query#some_fragment';

	function testUrl() {
		$url = new Url(self::TEST_URL);
		$this->assertEquals($url->getFull(), self::TEST_URL);
		$this->assertEquals($url->getScheme(), 'https');
		$this->assertEquals($url->getHost(), 'www.google.com');
		$this->assertEquals($url->getPort(), '80');
		$this->assertEquals($url->getPath(), '/some_path');
		$this->assertEquals($url->getQuery(), 'some=query');
		// $this->assertEquals($url->getParam('some'), 'query');
		// $this->assertEquals($url->getParams(), ['some' => 'query']);
		$this->assertEquals($url->getFragment(), 'some_fragment');
		$this->assertEquals($url->getUser(), 'user');
		$this->assertEquals($url->getPass(), 'pass');
	}
}
