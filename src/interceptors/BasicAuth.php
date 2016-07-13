<?php

require_once '../utils/security.php';
require_once '../RequestInterceptor.php';

/**
 * Extend this class to define a way to authenticate (retrieve and validate) a
 * user.
 */
abstract class AuthStrategy {
	protected $username;
	protected $password;

	private $_user;

	public function __construct() {}

	public function setCredentials(string $name, string $pass) {
		$this->username = $name;
		$this->password = $pass;
	}

	/**
	 * Wrapper for the _isValid() function, hiding the always-needed call to
	 * getUser().
	 */
	public function isValid(): bool {
		return $this->_isValid($this->getUser());
	}

	/**
	 * Wrapper to the _getUser() function, adding a cache layer.
	 */
	public function getUser(): array {
		if (empty($this->_user))
			$this->_user = $this->getUser();

		return $this->_user;
	}

	private abstract function _getUser(): array;

	private abstract function _isValid(array $user): bool;
}

/**
 * Example of AuthStrategy implementation, based on a common setup using a
 * MySQL database.
 */
class ExampleMysqlAuthStrategy extends AuthStrategy {
	private function _getUser(): array {
		$users = Database::run(
			'SELECT id, email, password FROM users WHERE username = ?',
			[ $this->username ]
		);

		if (count($users) === 0) {
			$res->setStatus(HttpStatus::Unauthorized);
			$res->setPayload([
				'title' => 'User not found',
				'detail' => 'There username provided for authentication does not match any of those stored'
			]);
			$res->send();
		}

		$user = $users[0];
		$user['id'] = (int) $user['id'];
		$user['username'] = $this->username;

		return $user;
	}

	private function _isValid(array $user): bool {
		// NOTE: the checking must conform the user registration process
		return !\utils\security\verify($this->password, $user['password']);
	}
}

/**
 * Basic authentication support as an interceptor.
 */
class BasicAuth implements RequestInterceptor {

	const USERS_SIGN_UP_PATH = '/users';

	private static $_authStrategy;

	public static function setAuthStrategy(AuthStrategy s) {
		self::$_authStrategy = s;
	}

	public function handle(Request $req, Response $res): Response {
		if (rtrim($req->getUrl()->getPath(), '/') === self::USERS_SIGN_UP_PATH)
			return $res;

		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			$res->setStatus(HttpStatus::Unauthorized);
			$res->addHeader('WWW-Authenticate', 'Basic');
			$res->setPayload([
				'title' => 'Unauthenticated',
				'detail' => 'The information you are trying to access requires authentication'
			]);
			$res->send();
		}

		$username = $_SERVER['PHP_AUTH_USER'];
		$password = $_SERVER['PHP_AUTH_PW'];

		if (empty(self::$_authStrategy))
			throw new InvalidArgumentException('An authentication strategy must be provided in order to identify the users in the system');

		self::$_authStrategy->setCredentials($username, $password);

		if (!self::$_authStrategy->isValid()) {
			$res->setStatus(HttpStatus::Unauthorized);
			$res->setPayload([
				'title' => 'Wrong password',
				'detail' => 'The password provided is not correct for the username'
			]);
			$res->send();
		}

		return $res;
	}
}

