<?php

namespace bed;

/**
 * Class representing each of the chunks embedded on the multipart request,
 * giving access to their different parts: headers and payload (body).
 */
class MultipartRelatedChunk {

	protected $headers;
	protected $payload;

	public function __construct(string $rawHeaders, string $payload) {
		$headerLines = explode("\n", $rawHeaders);
		foreach ($headerLines as $headerLine) {
			list($key, $value) = explode(':', $headerLine, 2);
			$this->headers[$key] = trim($value);
		}

		// Ensure that no meta-data is included
		$contentType = explode(';', $this->headers['Content-Type'], 2)[0];

		switch ($contentType) {
		case 'application/json':
			$this->payload = json_decode($payload, true);
			break;
		case 'text/xml':
		case 'application/xml':
			$this->payload = new SimpleXMLElement($payload);
			break;
		default:
			$this->payload = $payload;
			break;
		}
	}

	public function getHeaders(): array {
		return $this->headers;
	}

	public function getHeader(string $key): string {
		return $this->headers[$key];
	}

	public function getBody() {
		return $this->payload;
	}
}
