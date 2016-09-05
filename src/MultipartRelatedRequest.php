<?php

declare(strict_types=1);

namespace bed;

require_once 'MultipartRelatedChunk';

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

	protected $chunks;

	public function __construct(string $payload, string $boundary) {
		$parts = preg_split("/-+$boundary/", $payload);

		// Get rid of last "--"
		array_pop($parts);

		// Get rid of original headers
		array_shift($parts);

		foreach ($parts as $part) {
			$part = trim($part);

			if (!$part) continue;

			list($headers, $body) = explode("\n\n", $part, 2);
			$this->chunks[] = new MultipartRelatedChunk($headers, $body);
		}
	}

	public function getChunk(int $n) {
		return $this->chunks[$n] ?? null;
	}

	public function getChunks() {
		return $this->chunks;
	}
}
