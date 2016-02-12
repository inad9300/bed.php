<?php

require_once 'Dao.php';
require_once 'ErrorResponse.php';
require_once 'HttpStatus.php';
require_once 'Utils/Arrays.php';
require_once 'Utils/Dates.php';


// TODO: check for associative arrays in many functions


class Resource {

    private const _DEFAULT_LIMIT = 50; // TODO: generate dynamically
    private const _STRING_BASED_TYPES = ['string', 'date'];

    private $_config;


    public function __construct(array $config) {
        self::$_config = $config;
    }


    public function getInfo(): array {
        return $this->_config;
    }


    // GET /resources
    public function getMany(array $filter = null): array {}

    private function _getAccessibleFields(): array {
        $result = [];
        foreach ($this->_config['fields'] as $field) {
            if (/* TODO*/true) {
                $result[] = $field['name'];
            }
        }
        return $result;
    }

    // GET /resources/{id}
    public function getOne(int $id): array {
        (new Dao())->query(
            'SELECT ' . $this->_getAccessibleFields() . 
            ' FROM `' . $this->_config['name'] . '` WHERE id = ?',
            [$id]
        );
    }


    // POST /resources
    public function create(array $data): array {
        // INSERT INTO tbl_name (... cols ...) VALUES (...)[, (...)];
    }


    // PUT /resources
    public function replaceMany(array $data): array {}

    // PUT /resources/{id}
    public function replaceOne(int $id): array {}


    // PATCH /resources
    public function updateMany(array $data): array {}

    // PATCH /resources/{id}
    public function updateOne(int $id): array {}


    // DELETE /resources
    public function deleteMany(array $ids = null): array {}

    // DELETE /resources/{id}
    public function deleteOne(int $id): array {}


    // GET /resources/{id}/related
    public function getRelated(int $id, string $related, array $filter = null): array {}

    // POST /resources/{id}/related
    public function createRelated(int $id, string $related, array $data): array {}

    // DELETE /resources/{id}/related
    public function deleteRelated(int $id, string $related, array $ids = null): array {}

    // DELETE /resources/{id}/related/{id}
    public function deleteRelationship(int $id, string $related, int $idSubresource): array {}


    public function toJson(array $data): string {
        return json_encode($data);
    }


    private function _getFieldInfo(string $name): array {
        foreach ($this->_config['fields'] as $field) {
            if ($field['name'] === $name) {
                return $field;
            }
        }
        return false;
    }

    private function _validateOne(array $item) {
        foreach ($this->_config['fields'] as $field) {
            if ($field['required'] && ( !array_key_exists($field['name'], $item) || 
                                        (in_array($field['type'], Resource::_STRING_BASED_TYPES) && $item[$field['name']] === '') )) {
                return new ErrorResponse(HttpStatus::BadRequest,
                    'Mandatory field',
                    'The \'' . $field['name'] . '\' field is required, but no value was provided.');
            }
        }

        // TODO: transform database checks into valid PHP code, to support cases
        // such as "start_date < end_date"

        foreach ($item as $key => $value) {
            $field = $this->_getFieldInfo($key);

            if ($field === false) {
                continue;
                // TODO: allow configuring the response in this case (send error or ignore)
                /* return new ErrorResponse(HttpStatus::BadRequest, 
                    'Too much information provided', 
                    'No \'' . $field['name'] . '\' field is expected for \'' . $this->_config['name'] . '\'.'); */
            }

            if ($field['name'] === 'id') {
                continue; // Assume validity
            }

            switch ($field['type']) {
            case 'int':
                if (!is_int($value)) {
                    return new ErrorResponse(HttpStatus::BadRequest,
                        'Invalid data type',
                        'An integer was expected, but a different type was provided.');
                }

                if ($field['min'] && $value < $field['min']) {
                    return new ErrorResponse(HttpStatus::BadRequest,
                        'Value out of limits',
                        'The \'' . $field['name'] . '\' field cannot be less than ' . $field['min'] . ', provided: ' . $value . '.');
                }

                if ($field['max'] && $value > $field['max']) {
                    return new ErrorResponse(HttpStatus::BadRequest,
                        'Value out of limits',
                        'The \'' . $field['name'] . '\' field cannot be greater than ' . $field['min'] . ', provided: ' . $value . '.');
                }

                if ($field['in'] && !in_array($value, $field['in'])) {}
            break;
            case 'float':
                if (!is_float($value)) {}

                if ($field['min'] && $value < $field['min']) {}

                if ($field['max'] && $value > $field['max']) {}
            break;
            case 'decimal':
                if (!is_float($value)) {}

                if ($field['min'] && $value < $field['min']) {}

                if ($field['max'] && $value > $field['max']) {}
            break;
            case 'string':
                if (!is_string($value)) {}

                if ($field['min'] && strlen($value) < $field['min']) {}

                if ($field['max'] && strlen($value) < $field['max']) {}

                if ($field['in'] && !in_array($value, $field['in'])) {}

                // TODO: consider special cases such as URL, email or IP
                if ($field['regex'] && preg_match($field['regex'], $value) !== 1) {}
            break;
            case 'bool':
                if (!is_bool($value)) {}
            break;
            case 'date':
                if (!is_string($value) || !\Utils\Dates\isIso8601($value)) {}

                if ($field['min'] && strtotime($value) < strtotime($field['min'])) {}

                if ($field['max'] && strtotime($value) > strtotime($field['max'])) {}
            break;
            // case 'blob': break;
            // default: the configuration object is assumed to be valid
            }
        }
        return true;
    }

    public function validate(array $data): bool {
        if (\Utils\Arrays\isAssociative($data)) {
            foreach ($data as $item) {
                $result = $this->_validateOne($item);
                if ($result !== true) {
                    $result->send(); // Send first error found (IDEA: send batch of errors)
                }
            }
        } else {
            $result = $this->_validateOne($item);
            if ($result !== true) {
                $result->send();
            }
        }
    }

}