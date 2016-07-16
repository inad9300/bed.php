<?php

/**
 * Generic, simple class for data validation.
 */
class Validator {

	// Map from attributes (strings) to validators (functions)
	private $_rules;

	private $_errors;

	public function __construct(array $rules) {
		$this->_rules = $rules;
	}

	public function validate(array $data, array $attrs = null): self {
		$this->_errors = [];

		foreach ($this->_rules as $attr => $func) {
			if ($attrs !== null && !in_array($attr, $attrs))
				continue;

			$res = call_user_func($func, $data[$attr]);
			if ($res)
				$this->_errors[$attr] = is_array($res) ? $res : [$res];
		}

		return $this;
	}

	public function errors(): array {
		return $this->_errors;
	}

	public function isValid(): bool {
		return empty($this->_errors);
	}
}

