<?php

require_once 'Request.php';
require_once 'RequestInterceptor.php';

require_once 'Response.php';
require_once 'ResponseInterceptor.php';

/**
 * The key piece of any REST API back-end. Here is a summary of the features
 * it provides:
 * - React to URLs with custom callback functions, via get(), post(), put(),
 *   path(), delete() and options().
 * - Build routes with placeholders using the curly-braces style, e.g.
 *   /users/{id} - whose values are later reachable through args() and arg().
 * - Add middleware through implementations of the RequestInterceptor and
 *   ResponseInterceptor interfaces, either via setMiddleware(), which will
 *   apply to all the routes, or passing them as an array on each route.
 * - Respond to non-matching routes in a custom way with default().
 * - Send automatic responses to the OPTIONS HTTP method (implicit in the
 *   the default() method, so it is highly recommended to call it).
 */
class Router {

	private static $_pathPrefix = '';
	private static $_pathPrefixLen = 0;

	private static $_globalMiddleware;

	private static $_visitedRoutes;
	private static $_currentRoute;
	private static $_args = [];

	/**
	 * Establish a prefix for the routes, e.g. /api for a REST API.
	 */
	public static function setPathPrefix(string $url) {
		self::$_pathPrefix = trim($url, '/');
		self::$_pathPrefixLen = strlen(self::$_pathPrefix);
	}

	/**
	 * Return the previously stored route prefix.
	 */
	public static function getPathPrefix(): string {
		return self::$_pathPrefix;
	}

	/**
	 * Register middleware to be called on every route.
	 */
	public static function setMiddleware(array $middleware) {
		self::$_globalMiddleware = $middleware;
	}

	/**
	 * Return the registered global middleware.
	 */
	public static function getMiddleware(): array {
		return self::$_globalMiddleware;
	}

	/**
	 * Retrieve the current route "shape".
	 */
	public static function current() {
		return '/' . trim(self::$_currentRoute, '/');
	}

	/**
	 * Return the path arguments corresponding to the route placeholders.
	 */
	public static function args(): array {
		return self::$_args;
	}

	/**
	 * Return a particular path argument by its name.
	 */
	public static function arg(string $key): string {
		$arg = self::$_args[$key];
		if ($arg === null)
			throw new InvalidArgumentException('The required argument does not match with any defined route placeholder');

		return $arg;
	}

	/**
	 * Check if the current HTTP method corresponds to the given one (which
	 * would be the one defined for a route).
	 */
	private static function _isMethod(string $method): bool {
		return $method === Request::getInstance()->getMethod();
	}

	/**
	 * Check if a given string is a valid placeholder according to the rules
	 * of URI templating on its simplest form, e.g. {id}
	 */
	private static function _isValidPlaceholder(string $str): bool {
		return $str !== ''
			&& $str[0] === '{'
			&& substr($str, -1) === '}';
	}

	/**
	 * Check if a "route shape" matches with the current requested route.
	 */
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
			if ($route[$i] !== $realRoute[$i]) {
				if (!self::_isValidPlaceholder($route[$i]))
					return false;
				else
					self::$_args[$route[$i]] = $realRoute[$i];
			}

		return true;
	}

	/**
	 * Execute the given middleware, if it inherits from $baseInterceptor.
	 */
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

	/**
	 * Execute the middleware that is supposed to run before the request is
	 * handled.
	 */
	private static function _runBeforeMiddleware(
		array $middleware,
		Response $res
	): Response {
		return self::_runMiddleware($middleware, $res, 'RequestInterceptor');
	}

	/**
	 * Execute the middleware that is supposed to run after the request is
	 * handled.
	 */
	private static function _runAfterMiddleware(
		array $middleware,
		Response $res
	): Response {
		return self::_runMiddleware($middleware, $res, 'ResponseInterceptor');
	}

	/**
	 * Main routing method. Will determine the flow of execution: run the
	 * middleware, handle the request...
	 */
	private static function _doRoute(
		string $method,
		string $route,
		$middleware,
		$handler = null
	) {
		self::$_currentRoute = $route;
		self::$_visitedRoutes[] = [$route => $method];

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
	}

	/**
	 * Public method to register a handler for a certain GET route.
	 */
	public static function get(string $r, $m, $h = null) {
		self::_doRoute('GET', $r, $m, $h);
		self::_doRoute('HEAD', $r, $m, $h);
	}

	/**
	 * Handle POST requests.
	 */
	public static function post(string $r, $m, $h = null) {
		self::_doRoute('POST', $r, $m, $h);
	}

	/**
	 * Handle PUT requests.
	 */
	public static function put(string $r, $m, $h = null) {
		self::_doRoute('PUT', $r, $m, $h);
	}

	/**
	 * Handle PATCH requests.
	 */
	public static function patch(string $r, $m, $h = null) {
		self::_doRoute('PATCH', $r, $m, $h);
	}

	/**
	 * Handle DELETE requests.
	 */
	public static function delete(string $r, $m, $h = null) {
		self::_doRoute('DELETE', $r, $m, $h);
	}

	/**
	 * Handle OPTIONS requests.
	 */
	public static function options(string $r, $m, $h = null) {
		self::_doRoute('OPTIONS', $r, $m, $h);
	}

	/**
	 * Define a function to be in turn called at the moment default() gets
	 * called. It is though to be used as a default action for the routing.
	 * NOTE: must be called after all the routes are set up.
	 */
	public static function default(callable $handler) {
		self::_checkOptions();

		$res = $handler(Request::getInstance(), new Response());
		if (!$res instanceof Response)
			throw new RuntimeException(
				'Route handlers must return a Response object');

		$res->send();
	}

	/**
	 * Function to use the router information (up to when its called) to
	 * send proper responses to requests done via the OPTIONS method.
	 * NOTE: must be called after all the routes are set up, before the
	 * default() method gets executed.
	 */
	private static function _checkOptions() {
		if (!self::_isMethod('OPTIONS'))
			return;

		$allowedMethods = ['OPTIONS'];
		foreach (self::$_visitedRoutes as $route => $method)
			if ($route === self::$_currentRoute)
				$allowedMethods[] = $method;

		$res = new Response();
		$res->addHeader([
			'Allow' => implode(',', array_unique($allowedMethods))
		]);
		$res->send();
	}
}

