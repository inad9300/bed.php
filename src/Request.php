<?php

require_once 'Url.php';
require_once 'FileRequest.php';


class Request {

    private static $_instance = new Request();
    private static $_headers;
    private static $_url;
    private static $_payload;


    private function __construct() {}


    public static function getInstance(): Request {
        return self::$_instance;
    }


    public function getMethod(): string {
        return $this->getHeader('X-Http-Method-Override')
            || $_SERVER['REQUEST_METHOD'];
    }

    // NOTE: not exactly 1:1 relation with the raw HTTP request, where we have
    // only the partial URL in the first line, and then the "Host" header
    public function getUrl(): Uri {
        if (self::$_url === null) {
            self::$_url = new Uri();
        }
        return self::$_url;
    }

    public function getHttpVersion(): string {
        return $_SERVER['SERVER_PROTOCOL'];
    }

    public function getHeaders(): array {
        if (self::$_headers === null) {
            self::$_headers = getallheaders();
        }
        return self::$_headers;
    }

    public function getHeader(string $key): string {
        if (self::$_headers === null) {
            self::$_headers = getallheaders();
        }
        return self::$_headers[$key];
    }

    public function getPayload(): string {
        if (self::$_payload === null) {
            self::$_payload = file_get_contents('php://input');

            list($contentType, $contentTypeExtra) = explode(';', $this->getHeader('Content-Type'));

            // TODO: move to a "Formatter" or similar class
            switch ($contentType) {
            case 'application/json':
                self::$_payload = json_decode(self::$_payload, true);
                break;
            case 'application/xml':
                self::$_payload = new SimpleXMLElement(self::$_payload);
                break;
            case 'multipart/related':
                $boundary = substr(trim($contentTypeExtra), strlen('boundary='));
                self::$_payload = new FileRequest(self::$_payload, $boundary);
                break;
            // default: leave untouched (give the raw data)
            }
        }
        return self::$_payload;
    }

}