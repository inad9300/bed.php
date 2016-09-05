<?php

declare(strict_types=1);

namespace bed;

require_once '../RequestInterceptor.php';
require_once '../Request.php';
require_once '../Response.php';
require_once '../HttpStatus.php';

require_once 'AuthStrategy.php';

/**
 * Basic authentication support as an interceptor.
 */
class BasicAuth implements RequestInterceptor {

	protected $authStrategy;
	protected $signUpPath;

	public function __construct(AuthStrategy $s, string $signUpPath = null) {
		$this->authStrategy = $s;
		$this->signUpPath = $signUpPath ? trim($signUpPath, '/') : null;
	}

	public function handle(Request $req, Response $res): Response {
		// Skip if it is the path where the user is supposed to sign up
		if ($this->signUpPath &&
			$this->signUpPath === trim($req->getUrl()->getPath(), '/'))
			return $res;

		// A way to validate the user credentials is required
		if (!$this->authStrategy)
			throw new \InvalidArgumentException(
				'An authentication strategy must be provided in order to identify the users in the system');

		// Access forbidden if no credentials are provided
		if (!$_SERVER['PHP_AUTH_USER'])
			return $res->build(HttpStatus::Unauthorized, [
				'WWW-Authenticate' => 'Basic'
			], [
				'title' => 'Unauthenticated',
				'detail' => 'The information you are trying to access requires authentication'
			]);

		$user = $_SERVER['PHP_AUTH_USER'];
		$pass = $_SERVER['PHP_AUTH_PW'];

		// Validate the user-provided credentials
		if (!$this->authStrategy->checkCredentials($user, $pass))
			return $res->build(HttpStatus::Unauthorized, [], [
				'title' => 'Wrong password',
				'detail' => 'The password provided is not correct for the username'
			]);

		return $res;
	}
}
