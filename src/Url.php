<?php

/**
 * Class representing a URL with all its parts, as defined by parse_url()
 * (http://php.net/manual/en/function.parse-url.php). In addition, it provides
 * a way to access the different query parameters via the getParam() and
 * getParams() functions, and a way to access particular parts of the path
 * through getPathChunk() and getPathAfter(), allowing easy access to route
 * parameters.
 */
class Url {

	private $_full;
	private $_fullString;
	private $_pathChunks;
	private $_GET; // Analogous to the global $_GET variable

	/**
	 * Accept a URL, using the current one by default.
	 */
	public function __construct(string $url = null) {
		// NOTE: $_SERVER['REQUEST_SCHEME'] seems not to be reliable
		$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

		$this->_fullString = $url !== null
			? $url
			: $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$this->_full = parse_url($this->_fullString);
		if ($this->_full === false)
			throw new InvalidArgumentException(
				'Malformed URL: ' . $this->_fullString);
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

	/**
	 * Get the part of the path in the given position, e.g. getPathChunk(1)
	 * would return '3' given the path '/users/3'.
	 */
	public function getPathChunk(int $pos): string {
		$this->_fillPathChunks();

		return $this->_pathChunks[$pos];
	}

	/**
	 * Get the part of the path right after the one matching the argument, e.g.
	 * getPathAfter('users') would return '3' given the path '/users/3'.
	 */
	public function getPathAfter(string $pathPart) {
		$this->_fillPathChunks();

		for ($i = 0, $c = count($this->_pathChunks); $i < $c; ++$i)
			if ($this->_pathChunks[$i] === $pathPart && $i + 1 < $c)
				return $this->_pathChunks[$i + 1];

		return null;
	}

	private function _fillPathChunks() {
		if ($this->_pathChunks === null)
			$this->_pathChunks = explode('/', trim($this->_full['path'], '/'));
	}

	public function getQuery(): string {
		return $this->_full['query'];
	}

	public function getParam(string $key): string {
		return $this->getParams()[$key] ?? '';
	}

	public function getParams(): array {
		if ($this->_GET === null)
			parse_str($this->getQuery(), $this->_GET);

		return $this->_GET;
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
}

