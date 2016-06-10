<?php

class Url {

    private $_full;
    private $_fullString;


    public function __construct(string $urlString = null) {
        $this->_fullString = $urlString !== null
            ? $urlString
            : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $this->_full = parse_url($this->_fullString);
    }


    public function getFull(): string {
        return $this->_fullString;
    }

    public function getHost(): string {
        return $this->_full['host'];
    }

    public function getPath(): string {
        return $this->_full['path'];
    }

    public function getQuery(): string {
        return $this->_full['query'];
    }

    public function getParam(string $key): string {
        return $_GET[$key];
    }

    public function getParams(bool $onlyRest = false): array {
        return $_GET;
    }

    public function getFragment(): string {
        return $this->_full['fragment'];
    }

    public function getPort(): int {
        return $this->_full['port'];
    }

    public function getScheme(): string {
        return $this->_full['scheme'];
    }

    public function getUser(): string {
        return $this->_full['user'];
    }

    public function getPass(): string {
        return $this->_full['pass'];
    }

    public function __toString(): string {
        return $this->_fullString;
    }

}
