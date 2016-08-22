<?php

namespace bed;

/**
 * Implement this interface to define a way to authenticate (validate) a user.
 */
interface AuthStrategy {

	public function checkCredentials(string $username, string $password): bool;
}
