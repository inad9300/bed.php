<?php

namespace bed;

require_once 'Env.php';

/**
 * Representation of an HTTP response. The whole response can be built in the
 * constructor, or piece by piece afterwards, the pieces being the status, the
 * headers and the payload (body). Once all is ready, send it to the client.
 */
class Response {

	protected $status;
	protected $headers;
	protected $payload;

	public function __construct(
		int $status = 200,
		array $headers = [],
		$payload = null
	) {
		$this->status = $status;
		$this->headers = $headers;
		$this->payload = $payload;
	}

	// A concise way to override all the members if the Response object was
	// already instantiated. The functionality is equal to that of the
	// constructor
	public function build(
		int $status = 200,
		array $headers = [],
		$payload = null
	): Response {
		$this->status = $status;
		$this->headers = $headers;
		$this->payload = $payload;
		return this;
	}

	public function setStatus(int $status) {
		$this->status = $status;
		return $this;
	}

	public function getStatus(): int {
		return $this->status;
	}

	public function setHeaders(array $headers) {
		$this->headers = $headers;
		return $this;
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public function addHeaders(array $headers) {
		foreach ($headers as $key => $value)
			$this->headers[$key] = $value;

		return $this;
	}

	public function addHeader(string $key, string $value) {
		$this->headers[$key] = $value;
		return $this;
	}

	public function setBody($payload) {
		$this->payload = $payload;
		return $this;
	}

	public function getBody() {
		return $this->payload;
	}

	public function send() {
		// Send status
		$res = http_response_code($this->status);
		if ($res === false)
			throw new \RuntimeException('HTTP status could not be send');

		// Add default Content-Type header
		if (!array_key_exists('Content-Type', $this->headers))
			$this->headers['Content-Type'] = 'application/json';

		// Send headers
		foreach ($this->headers as $key => $value)
			header($key . ':' . $value);

		// Send payload, encoding everything as JSON
		$mask = JSON_PRESERVE_ZERO_FRACTION;
		if (!Env::isProd())
			$mask |= JSON_PRETTY_PRINT;

		$res = json_encode($this->payload ?? '', $mask);
		if ($res === false)
			throw new \RuntimeException('Error encoding data into JSON');

		echo $res;
		exit(0);
	}
}
