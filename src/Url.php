<?php

/**
 * Class representing a URL with all its parts, as defined by parse_url()
 * (http://php.net/manual/en/function.parse-url.php). In addition, it provides
 * a way to access the different query parameters via the getParam() and
 * getParams() functions.
 */
class Url {

	private $_full;
	private $_fullString;

	/**
	 * Accept a URL, using the current one by default.
	 */
	public function __construct(string $url = null) {
		$this->_fullString = $url !== null
			? $url
			: $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST']
				. $_SERVER['REQUEST_URI'];

		$this->_full = parse_url($this->_fullString);
		if ($this->_full === false)
			throw new InvalidArgumentException('Malformed URL');
	}

	public function getFull(): string {
		return $this->_fullString;
	}

	public function getHost(): string {
		return $this->_full['host'];
	}

	public function getPath(): string {
		return $this->_full['path'];
	}

	public function getQuery(): string {
		return $this->_full['query'];
	}

	public function getParam(string $key): string {
		return $_GET[$key];
	}

	public function getParams(): array {
		return $_GET;
	}

	public function getFragment(): string {
		return $this->_full['fragment'];
	}

	public function getPort(): int {
		return $this->_full['port'];
	}

	public function getScheme(): string {
		return $this->_full['scheme'];
	}

	public function getUser(): string {
		return $this->_full['user'];
	}

	public function getPass(): string {
		return $this->_full['pass'];
	}

	public function __toString(): string {
		return $this->_fullString;
	}

}
