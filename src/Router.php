<?php

namespace bed;

require_once 'Interceptor.php';

require_once 'Request.php';
require_once 'RequestInterceptor.php';

require_once 'Response.php';
require_once 'ResponseInterceptor.php';

/**
 * The key piece of any REST API back-end. Here is a summary of the features
 * it provides:
 * - React to URLs with custom callback functions via get(), post(), put(),
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

	/**
	 * Prefix for the routes, e.g. /api/v1 for a REST API.
	 */
	protected $pathPrefix;
	protected $pathPrefixLen;

	/**
	 * Middleware to be called on every route.
	 */
	protected $globalMiddleware;

	protected $defaultHandler;

	protected $visitedRoutes;
	protected $currentRoute;
	protected $args = [];

	public function __construct(
		string $pathPrefix = '',
		array $middleware = [],
		callable $defaultHandler = null
	) {
		$this->pathPrefix = trim($pathPrefix, '/');
		$this->pathPrefixLen = strlen($this->pathPrefix);

		$this->globalMiddleware = $middleware;

		$this->defaultHandler = $defaultHandler;
	}

	/**
	 * Return the previously stored route prefix.
	 */
	public function getPrefix(): string {
		return $this->pathPrefix;
	}

	/**
	 * Return the registered global middleware.
	 * IDEA accept the name of the middleware as parameter
	 */
	public function getMiddleware(): array {
		return $this->globalMiddleware;
	}

	/**
	 * Retrieve the current route "shape".
	 */
	public function getCurrent() {
		return '/' . trim($this->currentRoute, '/');
	}

	/**
	 * Return the path arguments corresponding to the route placeholders.
	 */
	public function getParams(): array {
		return $this->args;
	}

	/**
	 * Return a particular path argument by its name.
	 */
	public function getParam(string $key): string {
		$arg = $this->args[$key];
		if ($arg === null)
			throw new \InvalidArgumentException(
				'The required argument does not match with any defined route placeholder');

		return $arg;
	}

	/**
	 * Check if the current HTTP method corresponds to the given one (which
	 * would be the one defined for a route).
	 */
	protected function isMethod(string $method): bool {
		return $method === Request::getInstance()->getMethod();
	}

	/**
	 * Check if a given string is a valid placeholder according to the rules
	 * of URI templating on its simplest form, e.g. {id}
	 */
	protected function isValidPlaceholder(string $str): bool {
		return $str !== ''
			&& $str[0] === '{'
			&& substr($str, -1) === '}';
	}

	/**
	 * Check if a "route shape" matches with the current requested route.
	 */
	protected function isRoute(string $route): bool {
		$route = explode('/', trim($route, '/'));
		$reqPath = Request::getInstance()->getUrl()->getPath();
		$realRoute = explode('/', trim($reqPath, '/'));

		// Get rid of the prefix
		if ($realRoute[0] === $this->pathPrefix) {
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
				if (!$this->isValidPlaceholder($route[$i]))
					return false;
				else
					$this->args[$route[$i]] = $realRoute[$i];
			}

		return true;
	}

	/**
	 * Execute the middleware that is supposed to run before the request is
	 * handled.
	 */
	protected function runBeforeMiddleware(
		array $middleware,
		Response $res
	): Response {
		foreach ($middleware as $m)
			if ($m instanceof RequestInterceptor)
				$res = $m->handle(Request::getInstance(), $res);

		return $res;
	}

	/**
	 * Execute the middleware that is supposed to run after the request is
	 * handled.
	 */
	protected function runAfterMiddleware(
		array $middleware,
		Response $res
	): Response {
		foreach ($middleware as $m)
			if ($m instanceof ResponseInterceptor)
				$res = $m->handle(Request::getInstance(), $res);

		return $res;
	}

	/**
	 * Main routing method. Will determine the flow of execution: run the
	 * middleware, handle the request...
	 */
	protected function doRoute(
		string $method,
		string $route,
		$middleware,
		$handler = null
	) {
		$this->currentRoute = $route;
		$this->visitedRoutes[] = [$route => $method];

		if (!$this->isMethod($method) || !$this->isRoute($route))
			return;

		$res = new Response();

		if (isset($this->globalMiddleware))
			$res = $this->runBeforeMiddleware($this->globalMiddleware, $res);

		$noMiddleware = $handler === null;

		if ($noMiddleware)
			$handler = $middleware;
		else
			$res = $this->runBeforeMiddleware($middleware, $res);

		if (!is_callable($handler))
			throw new \InvalidArgumentException(
				'Only functions can be passed as route handlers');

		$res = $handler(Request::getInstance(), $res);
		if (!$res instanceof Response)
			throw new \RuntimeException(
				'Route handlers must return a Response object');

		if (!$noMiddleware)
			$res = $this->runAfterMiddleware($middleware, $res);

		if (isset($this->globalMiddleware))
			$res = $this->runAfterMiddleware($this->globalMiddleware, $res);

		$res->send();
	}

	/**
	 * Public method to register a handler for a certain GET route.
	 */
	public function get(string $r, $m, $h = null) {
		$this->doRoute('GET', $r, $m, $h);
		$this->doRoute('HEAD', $r, $m, $h);
	}

	/**
	 * Handle POST requests.
	 */
	public function post(string $r, $m, $h = null) {
		$this->doRoute('POST', $r, $m, $h);
	}

	/**
	 * Handle PUT requests.
	 */
	public function put(string $r, $m, $h = null) {
		$this->doRoute('PUT', $r, $m, $h);
	}

	/**
	 * Handle PATCH requests.
	 */
	public function patch(string $r, $m, $h = null) {
		$this->doRoute('PATCH', $r, $m, $h);
	}

	/**
	 * Handle DELETE requests.
	 */
	public function delete(string $r, $m, $h = null) {
		$this->doRoute('DELETE', $r, $m, $h);
	}

	/**
	 * Handle OPTIONS requests.
	 */
	public function options(string $r, $m, $h = null) {
		$this->doRoute('OPTIONS', $r, $m, $h);
	}

	/**
	 * Final checks to be done after having realize that no route matches the
	 * actual URL.
	 */
	public function end() {
		$this->checkOptions();
		$this->runDefault();
	}

	/**
	 * Function to use the router information (up to when its called) to
	 * send proper responses to requests done via the OPTIONS method.
	 * NOTE must be called after all the routes are set up, before executing
	 * the default route handler.
	 * TODO provide the allowed methods in some order. (?)
	 */
	protected function checkOptions() {
		if (!$this->isMethod('OPTIONS'))
			return;

		$allowedMethods = ['OPTIONS'];
		$allowedMethodsCount = 1;
		foreach ($this->visitedRoutes as $route => $method) {
			if ($route === $this->currentRoute) {
				$allowedMethods[] = $method;
				$allowedMethodsCount++;
			}
			// There are no more possible methods to allow, only GET, POST,
			// PUT, PATCH, DELETE and OPTIONS
			if ($allowedMethodsCount === 6)
				break;
		}

		$res = new Response();
		$res->addHeader([
			'Allow' => implode(',', array_unique($allowedMethods))
		]);
		$res->send();
	}

	/**
	 * Define a function to be in turn called at the moment default() gets
	 * called. It is thought to be used as a default action for the routing.
	 * NOTE must be called after all the routes are set up.
	 */
	protected function runDefault() {
		if (!$this->defaultHandler) return;

		$res = $this->defaultHandler(Request::getInstance(), new Response());
		if (!$res instanceof Response)
			throw new \RuntimeException(
				'Route handlers must return a Response object');

		$res->send();
	}
}
