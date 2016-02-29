<?php

require_once 'Transaction.php';
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
        $this->_config = $config;
    }


    public function getInfo(): array {
        return $this->_config;
    }


    private function _getAccessibleFields(): array {
        $result = [];
        foreach ($this->_config['fields'] as $field) {
            if (/* TODO */true) {
                $result[] = $field['name'];
            }
        }
        return $result;
    }


    private function _quote(array $cols): string {
        return implode(', ', array_map(function ($col) {
            return '`' . $col . '`';
        }, $cols));
    }

    private function _placeholders(int $n, string $symbol = '?'): string {
        $separator = ', ';
        $input = $symbol . $separator;
        return rtrim(str_repeat($input, $n), $separator);
    }

    // Returns only the fields of the resource in the specified subset, giving
    // them in the same order as in that subset.
    private function _selectFields(array $fields, array $resource): array {
        $finalResource = [];
        foreach ($fields as $field) {
            if (isset($resource[$field])) {
                $finalResource[$field] = $resource[$field];
            }
        }
        return $finalResource;
    }


    // GET /resources
    public function getMany(array $filter = null): array {
        $queryData = [];

        if ($filter['fields']) {
            // TODO: prepend the table name to each field
            $fields = array_filter(explode(',', $filter['fields']), function ($elem) {
                return in_array($elem, $this->_getAccessibleFields());
            });

            if (count($fields) < 1) {
                (new ErrorResponse(400, 'Too few fields selected', 'At least one valid field must be selected'));
            }
        } else {
            $fields = $this->_getAccessibleFields();
        }

        if ($filter['sort']) {
            $sortCriteria = [];
            $sortParams = explode(',', $filter['sort']);
            foreach ($sortParams as $param) {
                // TODO: check if field is sortable
                if ($param[0] === '-' ||
                    $param[0] === '+') {
                    $sortCriteria[] = '`' . substr($param, 1) . '` ' . $param[0] === '-' ? 'ASC' : 'DESC';
                } else {
                    $sortCriteria[] = '`' . $param . '` DESC';
                }
            }
            $order = implode(', ', $sortCriteria);
        } else {
            // TODO: get default order
        }

        $maxResults = $filter['limit'] ? ((int) $filter['limit']) : $this->_DEFAULT_LIMIT;

        $limit = 'LIMIT ?';
        $queryData[] = $maxResults;

        if ($filter['page']) {
            $limit .= ' OFFSET ?';
            $queryData[] = $maxResults * ((int) $filter['page']);
        }

        $joins = [];
        $conditions = [];

        if ($filter['filter']) {
            // NOTE: the filter's shape can be similar to "key:value,!value" or
            // "entity.key:value"

            $filters = explode(';', $filter['filter']);

            foreach ($filters as $f) {
                list($key, $value) = explode(':', $f);
                $values = explode(',', $value);

                if (strpos($key, '.') === false) { // Simple attribute
                    // TODO: check if $key is valid and filterable
                    $condition = '(';

                    $positives = [];
                    $negatives = [];
                    $isNull = false;
                    $isNotNull = false;

                    foreach ($values as $value) {
                        if ($value[0] === '!') {
                            if ($value === '!null')
                                $isNotNull = true;
                            else
                                $negatives[] = substr($value, 1);
                        } else {
                            if ($value === 'null')
                                $isNull = true;
                            else
                                $positives[] = $value;
                        }
                    }

                    $realKey = '`' . $this->_config['name'] . '.' . $key . '`';

                    $condition .= $realKey . ' IN (' . implode(', ', $positives) . ')';

                    $condition = ')';
                } else { // Attribute of a related entity
                    list($entity, $key) = explode('.', $key);
                    // TODO
                }
            }
        }

        $t = new Transaction();

        if ($filter['join']) {
            $joinedData = [];
            $relatedResources = explode(',', $filter['join']);

            foreach ($relatedResources as $relatedResource) {
                // TODO: validate that the values are properly formed
                list($resourceName, $resourceFilterRaw) = explode('(', $relatedResource);

                if (/* TODO: check if they are really related */true) {
                    $resource = new Resource(/* TODO: get config from resource name */);
                    $resourceFilterRaw = rtrim($resourceFilterRaw, ')');
                    $resourceFilter = [];
                    foreach (explode('&', $resourceFilterRaw) as $chunk) {
                        list($key, $value) = explode('=', $chunk);
                        $resourceFilter[rawurldecode($key)] = rawurldecode($value); // NOTE: urldecode may be a better candidate
                    }
                    $joinedData[$resourceName] = $resource->getMany($resourceFilter); // TODO: allow passing an in-progress transaction
                }
            }
        }

        $q = 'SELECT ' . _quote($fields) . ' FROM `' . $this->_config['name'] . '`';

        if (count($conditions) > 0) {
            $q .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $t->query($q, $queryData);
        $t->end();

        // TODO: prepare final result
    }

    // GET /resources/{id}
    public function getOne(int $id): array {
        (new Transaction())->query(
            'SELECT ' . _quote($this->_getAccessibleFields()) . 
            ' FROM `' . $this->_config['name'] . '` WHERE `id` = ?',
            [$id]
        );
    }

    // POST /resources
    public function create(array $data): array {
        $fields = $this->_getAccessibleFields();

        $q = 
            'INSERT INTO `' . $this->_config['name'] . '` (' . _quote($fields) .
            ') VALUES (' . _placeholders(count($fields)) . ')';
        
        $t = new Transaction();

        if (\Utils\Arrays\isAssociative($data)) {
            $t->query($q, _selectFields($fields, $data));
        } else {
            foreach ($data as $resource) {
                $t->query($q, _selectFields($fields, $resource));
            }
        }

        $t->end();
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