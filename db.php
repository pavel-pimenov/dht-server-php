<?php

/**
 * @author JhaoDa
 */

class DB {
    private $text;
    /** @var PDO */
    private $pdo;
    /** @var PDOStatement */
    private $statement;
    private $options = array();

    public function __construct($options) {
        $this->options = $options;

        try {
            $this->pdo = new PDO($this->options['DSN'], $this->options['username'], $this->options['password'], array(
                PDO::ATTR_PERSISTENT => TRUE,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ));
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, TRUE);
        } catch (PDOException $e) {
            die('Failed to create PDO instance');
        }
    }

    public function query($text, $params = array()) {
        try {
            $this->prepare($text);
            $this->statement->execute(empty($params) ? NULL : $params);

            $result = $this->statement->fetchAll(PDO::FETCH_ASSOC);
            $this->statement->closeCursor();

            return $result;
        } catch(PDOException $e) {
            die('Failed to execute the SQL statement: '.$e->getMessage());
        }
    }

    public function execute($text, $params = array()) {
        try {
            $this->prepare($text);
            $this->statement->execute(empty($params) ? NULL : $params);

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
