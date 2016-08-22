<?php

require_once '../src/MultipartRelatedRequest.php';

class MultipartRelatedRequestTest extends PHPUnit\Framework\TestCase {

	const TEST_REQUEST = 'POST /upload/drive/v3/files?uploadType=multipart HTTP/1.1
Host: www.googleapis.com
Authorization: Bearer your_auth_token
Content-Type: multipart/related; boundary=foo_bar_baz
Content-Length: number_of_bytes_in_entire_request_body

--foo_bar_baz
Content-Type: application/json; charset=UTF-8

{
  "name": "My File"
}

--foo_bar_baz
Content-Type: image/jpeg

JPEG data
--foo_bar_baz--';

	function testRequest() {
		$req = new MultipartRelatedRequest(self::TEST_REQUEST, 'foo_bar_baz');
		$chunks = $req->getChunks();

		$this->assertCount(2, $chunks);

		$this->assertEquals($chunks[0]->getBody(), [ 'name' => 'My File' ]);
		$this->assertEquals($chunks[1]->getBody(), 'JPEG data');

		$this->assertCount(1, $chunks[0]->getHeaders());
		$this->assertCount(1, $chunks[1]->getHeaders());
		$this->assertEquals(
			$chunks[0]->getHeader('Content-Type'),
			'application/json; charset=UTF-8'
		);
		$this->assertEquals(
			$chunks[1]->getHeader('Content-Type'),
			'image/jpeg'
		);
	}
}
