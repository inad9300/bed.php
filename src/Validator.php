<?php

// IDEA: define some kind of model, with a cast associated to attributes,
// default values (already in database)...

/**
 *
 */
class Validator {

	// Map from attributes (strings) to validators (functions)
	private $_rules;

	private $_lastErrors;

	public function __construct(array $rules) {
		$this->_rules = $rules;
	}

	public function validate(array $data, array $attrs = null): self {
		$errors = [];

		foreach ($this->_rules as $attr => $func) {
			if ($attrs !== null && !in_array($attr, $attrs))
				continue;

			$result = call_user_func($func, $data[$attr]);
			if ($result)
				$errors[$attr] = is_array($result) ? $result : [$result];
		}

		$this->_lastErrors = $errors;
		return $this;
	}

	public function errors(): array {
		return $this->_lastErrors;
	}

	public function isValid() {
		return empty($this->_lastErrors);
	}
}

