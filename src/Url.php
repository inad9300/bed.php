<?php

declare(strict_types=1);

namespace bed;

/**
 * Class representing a URL with all its parts, as defined by parse_url()
 * (http://php.net/manual/en/function.parse-url.php). In addition, it provides
 * a way to access the different query parameters via the getParam() and
 * getParams() functions, and a way to access particular parts of the path
 * through getPathChunk() and getPathAfter(), allowing easy access to route
 * parameters.
 */
class Url {

	protected $full;
	protected $fullString;
	protected $pathChunks;
	protected $_GET; // Analogous to the global $_GET variable

	/**
	 * Accept a URL, using the current one by default.
	 */
	public function __construct(string $url = null) {
		// NOTE $_SERVER['REQUEST_SCHEME'] seems not to be reliable
		$protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';

		$this->fullString = $url !== null
			? $url
			: $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$this->full = parse_url($this->fullString);
		if ($this->full === false)
			throw new \InvalidArgumentException(
				'Malformed URL: ' . $this->fullString);
	}

	public function getFull(): string {
		return $this->fullString;
	}

	public function getHost(): string {
		return $this->full['host'];
	}

	public function getPath(): string {
		return $this->full['path'];
	}

	/**
	 * Get the part of the path in the given position, e.g. getPathChunk(1)
	 * would return '3' given the path '/users/3'.
	 */
	public function getPathChunk(int $pos): string {
		$this->fillPathChunks();

		return $this->pathChunks[$pos];
	}

	/**
	 * Get the part of the path right after the one matching the argument, e.g.
	 * given the path '/users/3' getPathAfter('users') would return '3'.
	 */
	public function getPathAfter(string $pathPart) {
		$this->fillPathChunks();

		for ($i = 0, $c = count($this->pathChunks); $i < $c; ++$i)
			if ($this->pathChunks[$i] === $pathPart && $i + 1 < $c)
				return $this->pathChunks[$i + 1];

		return null;
	}

	protected function fillPathChunks() {
		if ($this->pathChunks === null)
			$this->pathChunks = explode('/', trim($this->full['path'], '/'));
	}

	public function getQuery(): string {
		return $this->full['query'];
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
		return $this->full['fragment'];
	}

	public function getPort(): int {
		return $this->full['port'];
	}

	public function getScheme(): string {
		return $this->full['scheme'];
	}

	public function getUser(): string {
		return $this->full['user'];
	}

	public function getPass(): string {
		return $this->full['pass'];
	}
}
