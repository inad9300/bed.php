<?php

require_once 'Environment.php';


class Response {

    private $_status;
    private $_headers;
    private $_payload;


    public function __construct(int $status = 200, array $headers = [], $payload = null) {
        $this->status = $status;
        $this->headers = $headers;
        $this->payload = $payload;
    }


    public function setStatus(int $status) {
        $this->_status = $status;
    }

    public function setHeaders(array $headers) {
        $this->_headers = $headers;
    }

    public function addHeaders(array $headers) {
        foreach ($headers as $h) {
            $this->_headers[] = $h;
        }
    }

    public function addHeader(string $header, string $value = null) {
        if ($value === null) {
            $this->_headers[] = $header;
        } else {
            $this->_headers[] = $header . ': ' . $value;
        }
    }

    public function setPayload($payload) {
        $this->_payload = $payload;
    }

    public function send() {
        // Send status
        $res = http_send_status($this->_status);
        if ($res === false) {
            throw new RuntimeException('HTTP status could not be send.');
        }

        // Send headers
        foreach ($this->headers as $h) {
            header($h);
        }

        // Send payload -- encode everything as JSON except strings (to allow
        // fine-grained control over it)
        if (!is_string($this->_payload)) {
            $mask = JSON_PRESERVE_ZERO_FRACTION;
            if (!Environment::isProd()) {
                $mask |= JSON_PRETTY_PRINT;
            }
            $this->_payload = json_encode($this->_payload, $mask);
            if ($this->_payload === false) {
                throw new RuntimeException('Error encoding data into JSON.');
            }
        }
        echo $this->_payload;
        exit;
    }

}