<?php

declare(strict_types=1);

namespace bed;

require_once 'Url.php';
require_once 'FileRequest.php';

/**
 * Representation of an HTTP request. Think of it as a simple way of accessing
 * the different parts of an HTTP message, in this order: the HTTP method, the
 * target URL, the HTTP version, the headers and the payload (body).
 */
class Request {

	protected $headers;
	protected $url;
	protected $payload;

	protected static $instance = new self;

	public static function getInstance(): self {
		return self::$instance;
	}

	public function getMethod(): string {
		return $this->getHeader('X-Http-Method-Override')
			?: $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Get the full URL.
	 */
	public function getUrl(): Url {
		if ($this->$url === null)
			$this->$url = new Url;

		return $this->$url;
	}

	public function getHttpVersion(): string {
		return $_SERVER['SERVER_PROTOCOL'];
	}

	public function getHeaders(): array {
		if ($this->$headers === null)
			$this->$headers = getallheaders();

		return $this->$headers;
	}

	public function getHeader(string $key): string {
		if (array_key_exists($key, $this->getHeaders()))
			return $this->getHeaders()[$key];

		return null;
	}

	public function getBody() {
		if ($this->$payload === null) {
			$this->$payload = file_get_contents('php://input');

			// Separate actual content type from its meta-data
			list($contentType, $contentTypeMeta)
				= explode(';', $this->getHeader('Content-Type'), 2);

			switch ($contentType) {
			case 'application/json':
				$this->$payload = json_decode($this->$payload, true);
				break;
			case 'text/xml':
			case 'application/xml':
				$this->$payload = new SimpleXMLElement($this->$payload);
				break;
			case 'multipart/related':
				$boundary = substr(
					trim($contentTypeMeta), strlen('boundary=')
				);
				$this->$payload = new FileRequest($this->$payload, $boundary);
				break;
			}
		}
		return $this->$payload;
	}
}
