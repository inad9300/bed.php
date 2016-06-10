<?php

/**
 * Class representing requests whose value for the Content-Type header is
 * "multipart/related". It parses and returns the different chunks embedded in
 * the original request.
 *
 * As a reference example of such request (taken from
 * https://developers.google.com/drive/v3/web/manage-uploads):
 *
 *		POST /upload/drive/v3/files?uploadType=multipart HTTP/1.1
 *		Host: www.googleapis.com
 *		Authorization: Bearer your_auth_token
 *		Content-Type: multipart/related; boundary=foo_bar_baz
 *		Content-Length: number_of_bytes_in_entire_request_body
 *
 *		--foo_bar_baz
 *		Content-Type: application/json; charset=UTF-8
 *
 *		{
 *		  "name": "My File"
 *		}
 *
 *		--foo_bar_baz
 *		Content-Type: image/jpeg
 *
 *		JPEG data
 *		--foo_bar_baz--
 */
class MultipartRelatedRequest {

	private $_chunks;

	public function __construct(string $payload, string $boundary) {
		$parts = preg_split("/-+$boundary/", $payload);
		array_pop($parts); // Get rid of last "--"

		foreach ($parts as $part) {
			$part = trim($part);

			if (empty($part)) continue;

			list($headers, $body) = explode("\n\n", $part, 2);
			$this->_chunks[] = new __MultipartRelatedChunk__($headers, $body);
		}
	}

	public function getChunks(int $n = null) {
		return $n === null ? $this->_chunks : $this->_chunks[$n];
	}

}

/**
 * Class representing each of the chunks embedded on the multipart request,
 * giving access to their different parts: headers and payload (body).
 */
class __MultipartRelatedChunk__ {

	private $_headers;
	private $_payload;

	public function __construct(string $rawHeaders, string $payload) {
		$headerLines = explode("\n", $rawHeaders);
		foreach ($headerLines as $headerLine) {
			list($key, $value) = explode(':', $headerLine, 2);
			$this->_headers[$key] = trim($value);
		}

		// Ensure that no meta-data is included
		$contentType = explode(';', $this->_headers['Content-Type'], 2)[0];

		switch ($contentType) {
		case 'application/json':
			$this->_payload = json_decode($payload, true);
			break;
		case 'text/xml':
		case 'application/xml':
			$this->_payload = new SimpleXMLElement($payload);
			break;
		default:
			$this->_payload = $payload;
			break;
		}
	}

	public function getHeaders(): array {
		return $this->_headers;
	}

	public function getHeader(string $key): string {
		return $this->_headers[$key];
	}

	public function getPayload() {
		return $this->_payload;
	}

}
