<?php

namespace Flylink\DHT;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Simlpe PDO wrapper.
 *
 * @author JhaoDa <jhaoda@gmail.com>
 */
class DB {
    private $text;

    /** @type PDO */
    private $pdo;

    /** @type PDOStatement */
    private $statement;

    private $options = [];

    public function __construct($options) {
        $this->options = $options;

        try {
            $this->pdo = new PDO($this->options['DSN'], $this->options['username'], $this->options['password'], [
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        } catch (PDOException $e) {
            die('Failed to create PDO instance');
        }
    }

    public function query($text, $params = []) {
        try {
            $this->prepare($text);
            $this->statement->execute(empty($params) ? null : $params);

            $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->statement->closeCursor();

            return $result;
        } catch(PDOException $e) {
            die('Failed to execute the SQL statement: '.$e->getMessage());
        }
    }

    public function execute($text, $params = []) {
        try {
            $this->prepare($text);
            $this->statement->execute(empty($params) ? null : $params);

            return $this->statement->rowCount();
        } catch(PDOException $e) {
            die('Failed to execute the SQL statement: '.$e->getMessage());
        }
    }

    private function prepare($text) {
        $this->text = str_replace('{table}', $this->options['table'], $text);
        $this->statement = $this->pdo->prepare($this->text);
    }
}
