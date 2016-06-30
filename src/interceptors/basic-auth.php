<?php

require_once '../utils/security.php';
require_once '../RequestInterceptor.php';

class Auth implements RequestInterceptor {

	const USERS_SIGN_UP_PATH = '/users';

	private static $_user;

	public static function user(): array {
		return self::$_user;
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

		$users = Database::run(
			'SELECT id, email, password FROM users WHERE username = ?',
			[ $username ]
		);

		if (count($users) === 0) {
			$res->setStatus(HttpStatus::Unauthorized);
			$res->setPayload([
				'title' => 'User not found',
				'detail' => 'There username provided for authentication does not match any of those stored'
			]);
			$res->send();
		}

		self::$_user = $users[0];
		self::$_user['username'] = $username;
		self::$_user['id'] = (int) self::$_user['id'];

		if (!utils\security\verify($password, self::$_user['password'])) {
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

