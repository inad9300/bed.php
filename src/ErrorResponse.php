<?php

class ErrorResponse extends Response {

    private static $_baseUrl;

    public static function setBaseUrl(string $url) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('The provided value must be a valid URL.');
        }
        self::$_baseUrl = rtrim($url, '/') . '/';
    }

    public function __construct(int $status = 400, string $title = '', string $message = '', int $code = null) {
        $payload = [
            'title' => $title,
            'message' => $message
        ];
        
        if ($code) {
            $payload['code'] = $code;
            if (self::$_baseUrl) {
                $payload['page'] = self::$_baseUrl . $code;
            }
        }
        
        parent::__construct($status, [], $payload);
    }

    // TODO: setTitle, setMessage, setCode

}