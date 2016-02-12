<?php

/* Example (from https://developers.google.com/drive/v2/web/manage-uploads):

    POST /upload/drive/v2/files?uploadType=multipart HTTP/1.1
    Host: www.googleapis.com
    Authorization: Bearer your_auth_token
    Content-Type: multipart/related; boundary=foo_bar_baz
    Content-Length: number_of_bytes_in_entire_request_body

    --foo_bar_baz
    Content-Type: application/json; charset=UTF-8

    {
      "title": "My File"
    }

    --foo_bar_baz
    Content-Type: image/jpeg

    JPEG data
    --foo_bar_baz--

*/


class __MultipartRelatedChunk__ {

    private $_headers;
    private $_payload;

    public function __construct(string $rawHeaders, string $payload) {
        $headerLines = explode("\n", $rawHeaders);
        foreach ($headerLines as $headerLine) {
            list($key, $value) = explode(":", $headerLine);
            $this->_headers[$key] = trim($value);
        }

        list($contentType, $contentTypeExtra) = explode(';', $this->_headers['Content-Type']);

        switch ($contentType) {
        case 'application/json':
            $this->_payload = json_decode($payload, true);
            break;
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


class MultipartRelatedRequest {

    private $_chunks;

    public function __construct(string $payload, string $boundary) {
        $parts = preg_split("/-+$boundary/", $payload);
        array_pop($parts); // Get rid of last "--"

        foreach ($parts as $part) {
            $part = trim($part);

            if (empty($part)) continue;

            list($headers, $body) = explode("\n\n", $part);
            $this->_chunks[] = new __MultipartRelatedChunk__($headers, $body);
        }
    }

    public function getChunks(int $n = null) {
        return $n === null ? $this->_chunks : $this->_chunks[$n];
    }

}