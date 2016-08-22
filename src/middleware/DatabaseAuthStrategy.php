<?php

namespace bed;

require_once 'AuthStrategy.php';

require_once '../utils/security/hash.php';
require_once '../Database.php';
require_once '../Col.php';
require_once '../HttpStatus.php';

/**
 * Example of AuthStrategy implementation, based on a common setup using a SQL
 * database.
 */
class DatabaseAuthStrategy implements AuthStrategy {

	protected $user;

	public function checkCredentials(string $username, string $pass): bool {
		// NOTE the checking must conform with the user registration process
		return !\bed\utils\security\verify(
			$pass,
			$this->getUser($username)['password']
		);
	}

	public function getUser(string $username = null): array {
		if (!$this->user) {
			if (!$username)
				throw new \RuntimeException(
					'No user was authenticated yet, the username parameter is mandatory');

			// IDEA separate into AuthDao / UsersDao, both to clean things up
			// and to show an example of how to work with a child of Dao
			$data = Database::run(
				'SELECT id, username, email, password
				FROM users WHERE username = ?',
				[$username],
				[Col::INT()]
			);
			if (!$data)
				return $res->build(HttpStatus::NotFound, [], [
					'title' => 'User not found',
					'detail' => 'No user exists with the provided username'
				]);

			$this->user = $data[0];
		}
		return $this->user;
	}
}
