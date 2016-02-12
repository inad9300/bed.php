<?php

class Url {

    private $_full;
    private $_fullString;

    private const _REST_PARAMS = ['fields', 'limit', 'page', 'sort', 'filter', 'join'];


    public function __construct(string $urlString = null) {
        if ($urlString === null) {
            $this->_fullString = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        } else {
            $this->_fullString = $urlString;
        }
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

    private function _filterRestParams(array $params) {
        return array_filter($params, function ($key) {
            return in_array($key, Url::_REST_PARAMS);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getParams(bool $onlyRest = false): array {
        return $onlyRest
            ? _filterRestParams($_GET)
            : $_GET;
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