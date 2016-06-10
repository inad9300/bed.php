<?php

require_once 'Url.php';
require_once 'FileRequest.php';

/**
 * Representation of an HTTP request. Think of it as a simple way of accessing
 * the different parts of an HTTP message, in this order: the HTTP method, the
 * target URL, the HTTP version, the headers and the payload (body).
 *
 * It is implemented applying the Singleton pattern.
 */
class Request {

	private static $_instance = new Request();
	private static $_headers;
	private static $_url;
	private static $_payload;

	public static function getInstance(): Request {
		return self::$_instance;
	}

	public function getMethod(): string {
		return $this->getHeader('X-Http-Method-Override')
			|| $_SERVER['REQUEST_METHOD'];
	}

	// Get the full URL
	public function getUrl(): Url {
		if (self::$_url === null)
			self::$_url = new Url();

		return self::$_url;
	}

	public function getHttpVersion(): string {
		return $_SERVER['SERVER_PROTOCOL'];
	}

	public function getHeaders(): array {
		if (self::$_headers === null)
			self::$_headers = getallheaders();

		return self::$_headers;
	}

	public function getHeader(string $key): string {
		return ($this->getHeaders())[$key];
	}

	public function getPayload() {
		if (self::$_payload === null) {
			self::$_payload = file_get_contents('php://input');

			// Separate actual content type from its meta-data
			list($contentType, $contentTypeMeta)
				= explode(';', $this->getHeader('Content-Type'), 2);

			switch ($contentType) {
			case 'application/json':
				self::$_payload = json_decode(self::$_payload, true);
				break;
			case 'text/xml':
			case 'application/xml':
				self::$_payload = new SimpleXMLElement(self::$_payload);
				break;
			case 'multipart/related':
				$boundary = substr(
					trim($contentTypeMeta), strlen('boundary=')
				);
				self::$_payload = new FileRequest(self::$_payload, $boundary);
				break;
			}
		}
		return self::$_payload;
	}

}
