<?php

// TODO: re-think the interceptor concept
// IDEA: pass *::class as middleware, as oppose to class instances

require_once 'Request.php';
require_once 'RequestInterceptor.php';

require_once 'Response.php';
require_once 'ResponseInterceptor.php';

/**
 * The key piece of any REST API back-end. It provides four features:
 * - React to URLs with custom callback functions, via get(), post(), put(),
 *   path(), delete() and options().
 * - Build routes with placeholders using the curly-braces style, e.g.
 *   /users/{id}.
 * - Add middleware through implementations of the RequestInterceptor and
 *   ResponseInterceptor interfaces, either via setMiddleware(), which will
 *   apply to all the routes, or passing them as an array on each route.
 * - Respond to non-matching routes in a custom way with default().
 */
class Router {

	private static $_pathPrefix = '';
	private static $_pathPrefixLen = 0;
	private static $_globalMiddleware;

	public static function setPathPrefix(string $url) {
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
		return $str !== ''
			&& $str[0] === '{'
			&& substr($str, -1) === '}';
	}

	private static function _isRoute(string $route): bool {
		$route = explode('/', trim($route, '/'));
		$reqPath = Request::getInstance()->getUrl()->getPath();
		$realRoute = explode('/', trim($reqPath, '/'));

		// Get rid of the prefix
		if ($realRoute[0] === self::$_pathPrefix) {
			if (count($realRoute) > 1)
				array_shift($realRoute);
			else
				$realRoute[0] = '';
		} else
			return false;

		if ($realRoute === $route)
			return true;

		$parts = count($realRoute);

		if ($parts !== count($route))
			return false;

		for ($i = 0; $i < $parts; ++$i)
			if ($route[$i] !== $realRoute[$i] &&
				!self::_isValidPlaceholder($route[$i]))
				return false;

		return true;
	}

	private static function _runMiddleware(
		array $middleware,
		Response $res,
		string $baseInterceptor
	): Response {
		foreach ($middleware as $m)
			if (is_subclass_of($m, $baseInterceptor))
				$res = (new $m)->handle(Request::getInstance(), $res);

		return $res;
	}

	private static function _runBeforeMiddleware(
		array $middleware,
		Response $res
	): Response {
		return self::_runMiddleware($middleware, $res, 'RequestInterceptor');
	}

	private static function _runAfterMiddleware(
		array $middleware,
		Response $res
	): Response {
		return self::_runMiddleware($middleware, $res, 'ResponseInterceptor');
	}

	private static function _doRoute(
		string $method,
		string $route,
		$middleware,
		$handler = null
	) {
		if (!self::_isMethod($method) || !self::_isRoute($route))
			return;

		$res = new Response();

		if (isset(self::$_globalMiddleware))
			$res = self::_runBeforeMiddleware(self::$_globalMiddleware, $res);

		$noMiddleware = $handler === null;

		if ($noMiddleware)
			$handler = $middleware;
		else
			$res = self::_runBeforeMiddleware($middleware, $res);

		if (!is_callable($handler))
			throw new InvalidArgumentException(
				'Only functions can be passed as route handlers');

		$res = $handler(Request::getInstance(), $res);
		if (!$res instanceof Response)
			throw new RuntimeException(
				'Route handlers must return a Response object');

		if (!$noMiddleware)
			$res = self::_runAfterMiddleware($middleware, $res);

		if (isset(self::$_globalMiddleware))
			$res = self::_runAfterMiddleware(self::$_globalMiddleware, $res);

		$res->send();
		exit(0);
	}

	public static function get(string $r, $m, $h = null) {
		self::_doRoute('GET', $r, $m, $h);
		self::_doRoute('HEAD', $r, $m, $h);
	}

	public static function post(string $r, $m, $h = null) {
		self::_doRoute('POST', $r, $m, $h);
	}

	public static function put(string $r, $m, $h = null) {
		self::_doRoute('PUT', $r, $m, $h);
	}

	public static function patch(string $r, $m, $h = null) {
		self::_doRoute('PATCH', $r, $m, $h);
	}

	public static function delete(string $r, $m, $h = null) {
		self::_doRoute('DELETE', $r, $m, $h);
	}

	// IDEA: use the knowledge of the router to automate the response to
	// OPTIONS requests
	public static function options(string $r, $m, $h = null) {
		self::_doRoute('OPTIONS', $r, $m, $h);
	}

	// IDEA: should this be middleware-aware as well?
	// IDEA: do something (exit? return?) if the route doesn't start with the
	// API prefix
	// NOTE: must be called after all the routes are set up.
	public static function default(callable $handler) {
		$res = $handler(Request::getInstance(), new Response());
		if (!$res instanceof Response)
			throw new RuntimeException(
				'Route handlers must return a Response object');

		$res->send();
		exit(0);
	}

}
