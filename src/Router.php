<?php

// TODO
// - Hooks (?)
// - Add authentication (via middleware, not here)
// - Support HEAD, CONNECT, TRACE and OPTIONS methods

require_once 'Request.php';
require_once 'Response.php';


class Router {

    private static $_baseUrl;

    public static function setBaseUrl(string $url) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('The provided value must be a valid URL.');
        }
        self::$_baseUrl = rtrim($url, '/') . '/';
    }

    public static function getBaseUrl(): string {
        return self::$_baseUrl;
    }

    private static function _isMethod(string $method): bool {
        return $method === Request::getInstance()->getMethod();
    }

    private static function _isValidPlaceholder(string $potentialPlaceholder): bool {
        return $potentialPlaceholder[0] === '{' 
            && substr($potentialPlaceholder, -1) === '}');
    }

    private static function _isRoute(string $route): bool {
        $route = explode('/', trim($route, '/'));
        $realRoute = explode('/', trim(Request::getInstance()->getUrl()->getPath(), '/'));

        $parts = count($realRoute);

        if ($parts !== count($route))
            return false;

        for ($i = 0; $i < $parts; ++$i) {
            if ($route[$i] !== $realRoute[$i] ||
                !self::_isValidPlaceholder($route[$i]))
                return false;
        }

        return true;
    }

    private static function _doRoute(string $method, string $route, callable $handler) {
        if (self::_isMethod($method) &&
            self::_isRoute($route)) {
            call_user_func($handler, Request::getInstance(), new Response());
            exit;
        }
    }

    public static function get(string $route, callable $handler) {
        self::_doRoute('GET', $route, $handler);
    }

    public static function post(string $route, callable $handler) {
        self::_doRoute('POST', $route, $handler);
    }

    public static function put(string $route, callable $handler) {
        self::_doRoute('PUT', $route, $handler);
    }

    public static function patch(string $route, callable $handler) {
        self::_doRoute('PATCH', $route, $handler);
    }

    public static function delete(string $route, callable $handler) {
        self::_doRoute('DELETE', $route, $handler);
    }

    public static function default(callable $handler) {
        call_user_func($handler, Request::getInstance(), new Response());
    }

}