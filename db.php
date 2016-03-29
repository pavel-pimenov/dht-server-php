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
            
/*
$this->pdo = new PDO($this->options['DSN'], $this->options['username'], $this->options['password']);
*/
	    $this->pdo = new PDO('sqlite:/db/dht.sqlite');
	    $this->pdo->setAttribute(PDO::ATTR_PERSISTENT, true);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $this->pdo->query('PRAGMA journal_mode=OFF');
	    #this->pdo->query('PRAGMA temp_store=OFF');
	    $this->pdo->query('create table if not exists dht_info (cid char(39) primary key, ip varchar(15) not null, port int not null, conn_count int not null default 0, user_agent varchar(256), live int not null default 0, last_time datetime default current_timestamp);');
        } catch (PDOException $e) {
            die('Failed to create PDO instance' .$e->getMessage());
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
