<?php

/*
    $dao = new Dao();
    $dao->query('SELECT * FROM users WHERE id = ?', [3]);
    $dao->persist();
*/

class Dao {

    private const _DEFAULT_TRIM_MASK = ' \t\n\r\0\x0B';

    private $_dbh; // Database handler


    public function __construct() {
        $this->_dbh = new PDO('mysql:host=localhost;dbname=test;charset=utf8mb4', '$user', '$pass', [
            PDO::ATTR_PERSISTENT => true, // ?
            PDO::ATTR_CASE => PDO::CASE_LOWER,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $res = $this->_dbh->beginTransaction();
        if ($res === false) {
            throw new RuntimeException('Transaction could not be started.');
        }
    }

    function __destruct() {
        if ($this->_dbh !== null) {
            $res = $this->_dbh->rollBack();
            if ($res === false) {
                throw new RuntimeException('Impossible to rollback.');
            }
        }
    }


    public function query(string $q, array $params = []) {
        try {
            $stmt = $this->_dbh->prepare($q);
            $res = $stmt->execute($params);
            if ($res === false) {
                throw new RuntimeException('Query execution failed.');
            }
            if (strtoupper(substr(ltrim($q, '(' . Dao::_DEFAULT_TRIM_MASK), 0, 6)) === 'SELECT') {
                return $stmt->fetchAll();
            }
            return $stmt->rowCount();
        } catch (Exception $e) { // Useful?
            $res = $this->_dbh->rollBack();
            if ($res === false) {
                throw new RuntimeException('Impossible to rollback.');
            }
            throw $e;
        }
    }

    // TODO: persist() may not be called if only a SELECT is performed. Check if
    // no committing in such case has any consequences.
    public function persist() {
        $res = $this->_dbh->commit();
        if ($res === false) {
            $this->_dbh->rollBack();
            if ($res === false) {
                throw new RuntimeException('Impossible to commit or rollback.');
            }
            throw new RuntimeException('Commitment failed.');
        }
    }

/*
    PARAM_BOOL, PARAM_NULL, PARAM_INT, PARAM_STR, PARAM_LOB
*/
/*
    $stmt = $this->_dbh->prepare('... :name ...');
    $stmt->bindValue(':name', $name, PDO::PARAM_STR);

    $name = 'Dan';
    $stmt->execute();
*/
/*
    $stmt = $this->_dbh->prepare('... ? ...');
    $stmt->bindValue(1, $name, PDO::PARAM_STR);

    $name = 'Dan';
    $stmt->execute();
*/
/*
    $stmt = $this->_dbh->prepare('... ? ...');

    $stmt->execute(array('Dan'));
*/
/*
    while ($row = $stmt->fetch()) ...

    $result = $stmt->fetchAll();

    $count = $stmt->rowCount();
*/
/*
    NOTE for files:
    - http://php.net/manual/en/pdo.lobs.php
    - http://php.net/manual/en/features.file-upload.put-method.php
*/

}