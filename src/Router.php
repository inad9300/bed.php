<?php

require_once 'Request.php';
require_once 'RequestInterceptor.php';

require_once 'Response.php';
require_once 'ResponseInterceptor.php';


class Router {

    private static $_pathPrefix = '';
    private static $_pathPrefixLen = 0;
    private static $_globalMiddleware;

    public static function setPathPrefix(string $url) {
        if (filter_var($url, FILTER_VALIDATE_URL) === false)
            throw new InvalidArgumentException('The provided value must be a valid URL');

        self::$_pathPrefix = trim($url, '/');
        self::$_pathPrefixLen = strlen(self::$_pathPrefix);
    }

    public static function getPathPrefix(): string {
        return self::$_pathPrefix;
    }

    public static function setMiddleware(array $middleware) {
        self::$_globalMiddleware = $middleware;
    }

    public static function getMiddleware(): array {
        return self::$_globalMiddleware;
    }

    private static function _isMethod(string $method): bool {
        return $method === Request::getInstance()->getMethod();
    }

    private static function _isValidPlaceholder(string $str): bool {
        return $str[0] === '{'
            && substr($str, -1) === '}');
    }

    private static function _isRoute(string $route): bool {
        $route = explode('/', trim($route, '/'));
        $reqPath = Request::getInstance()->getUrl()->getPath();
        $realRoute = explode('/', trim($reqPath, '/'));

        // Get rid of the prefix
        if ($realRoute[0] === self::$_pathPrefix)
            array_shift($realRoute);

        $parts = count($realRoute);

        if ($parts !== count($route))
            return false;

        for ($i = 0; $i < $parts; ++$i)
            if ($route[$i] !== $realRoute[$i] ||
                !self::_isValidPlaceholder($route[$i]))
                return false;

        return true;
    }

    private static function _runBeforeMiddleware(array $middleware) {
        foreach ($middleware as $m)
            if ($m instanceof RequestInterceptor) {
                $res = $m->handle(Request::getInstance());

                // An optional Response can be returned
                if ($res !== null && $res instanceof Response)
                    $res->send();
            }
    }

    private function _runAfterMiddleware(array $middleware, Response $res): Response {
        foreach ($middleware as $m)
            if ($m instanceof ResponseInterceptor)
                $res = $m->handle($res);

        return $res;
    }

    private static function _doRoute(string $method, string $route, $middleware, $handler = null) {
        if (!self::_isMethod($method) || !self::_isRoute($route))
            return;

        if (isset(self::$_globalMiddleware))
            _runBeforeMiddleware(self::$_globalMiddleware);

        if ($handler === null)
            $handler = $middleware;
        else
            _runBeforeMiddleware($middleware);

        if (!is_callable($handler))
            throw new InvalidArgumentException('Only functions can be passed as route handlers');

        $res = call_user_func($handler, Request::getInstance(), new Response());
        if (!$res instanceof Response)
            throw new RuntimeException('Route handlers must return a Response object');

        if ($handler !== null)
            $res = _runAfterMiddleware($middleware, $res);

        if (isset(self::$_globalMiddleware))
            $res = _runAfterMiddleware(self::$_globalMiddleware, $res);

        $res->send();
        exit;
    }

    public static function get(string $route, $middleware, $handler = null) {
        self::_doRoute('GET', $route, $middleware, $handler);
        self::_doRoute('HEAD', $route, $middleware, $handler);
    }

    public static function post(string $route, $middleware, $handler = null) {
        self::_doRoute('POST', $route, $middleware, $handler);
    }

    public static function put(string $route, $middleware, $handler = null) {
        self::_doRoute('PUT', $route, $middleware, $handler);
    }

    public static function patch(string $route, $middleware, $handler = null) {
        self::_doRoute('PATCH', $route, $middleware, $handler);
    }

    public static function delete(string $route, $middleware, $handler = null) {
        self::_doRoute('DELETE', $route, $middleware, $handler);
    }

    // IDEA: the knowledge of the router should be used to automate the
    // response to OPTIONS requests
    public static function options(string $route, $middleware, $handler = null) {
        self::_doRoute('OPTIONS', $route, $middleware, $handler);
    }

    // IDEA: should this be middleware-aware as well?
    // NOTE: must be called after all the routes are set up.
    public static function default(callable $handler) {
        $res = call_user_func($handler, Request::getInstance(), new Response());
        if (!$res instanceof Response)
            throw new RuntimeException('Route handlers must return a Response object');

        $res->send();
        exit;
    }

}
