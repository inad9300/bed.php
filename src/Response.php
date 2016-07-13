<?php

require_once 'Env.php';

/**
 * Representation of an HTTP response. The whole response can be built in the
 * constructor, or piece by piece afterwards, the pieces being the status, the
 * headers and the payload (body). Once all is ready, send it to the client.
 */
class Response {

	private $_status;
	private $_headers;
	private $_payload;

	public function __construct(
		int $status = 200,
		array $headers = [],
		$payload = null
	) {
		$this->_status = $status;
		$this->_headers = $headers;
		$this->_payload = $payload;
	}

	public function setStatus(int $status) {
		$this->_status = $status;
		return $this;
	}

	public function getStatus(): int {
		return $this->_status;
	}

	public function setHeaders(array $headers) {
		$this->_headers = $headers;
		return $this;
	}

	public function getHeaders(): array {
		return $this->_headers;
	}

	public function addHeaders(array $headers) {
		foreach ($headers as $key => $value)
			$this->_headers[$key] = $value;

		return $this;
	}

	public function addHeader(string $key, string $value) {
		$this->_headers[$key] = $value;
		return $this;
	}

	public function setPayload($payload) {
		$this->_payload = $payload;
		return $this;
	}

	public function getPayload() {
		return $this->_payload;
	}

	public function send() {
		// Send status
		$res = http_response_code($this->_status);
		if ($res === false)
			throw new RuntimeException('HTTP status could not be send');

		// Add default Content-Type header
		if (!array_key_exists('Content-Type', $this->_headers))
			$this->_headers['Content-Type'] = 'application/json';

		// Send headers
		foreach ($this->_headers as $key => $value)
			header($key . ':' . $value);

		// Send payload, encoding everything as JSON
		$mask = JSON_PRESERVE_ZERO_FRACTION;
		if (!Env::isProd())
			$mask |= JSON_PRETTY_PRINT;

		$res = json_encode($this->_payload ?? '', $mask);
		if ($res === false)
			throw new RuntimeException('Error encoding data into JSON');

		echo $res;
		exit(0);
	}
}

