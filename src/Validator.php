<?php

namespace bed;

/**
 * Generic, simple class for data validation.
 */
class Validator {

	// Map from attributes (strings) to validators (functions which return one
	// or more error messages, thus a string or an array of strings).
	protected $rules;

	protected $errors;

	public function __construct(array $rules) {
		$this->rules = $rules;
	}

	public function validate(array $data, array $attrs = null): bool {
		$this->errors = [];
		$attrsIsNotNull = $attrs !== null;

		foreach ($this->rules as $attr => $func) {
			if ($attrsIsNotNull && !in_array($attr, $attrs))
				continue;

			// The $data is passed again for the cases when a comparison
			// between different fields needs to be done
			$res = call_user_func($func, $data[$attr], $data);
			if ($res)
				$this->errors[$attr] = is_array($res) ? $res : [$res];
		}

		return $this->isValid();
	}

	public function isValid(): bool {
		return !!$this->errors;
	}

	public function errors(): array {
		return $this->errors;
	}
}
