DHT service for FlylinkDC++.
==============

DB scheme:
```sql
CREATE TABLE `dht_info` (
    `cid` CHAR(39) NOT NULL,
    `ip` VARCHAR(15) NOT NULL,
    `port` SMALLINT(5) UNSIGNED NOT NULL,
    `conn_count` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
    `user_agent` VARCHAR(256) NULL DEFAULT NULL,
    `live` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
    `last_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`cid`)
) ENGINE=InnoDB;

ALTER TABLE dht_info ADD INDEX i_dht_info_live (live) 

```

Create file `config.php` somewhere outside the doc_root:

```php
<?php

return [
    'DSN'      => 'mysql:host=127.0.0.1;dbname=dht_test',
    'username' => 'root',
    'password' => '111',
    'table'    => 'dht_info'
];
```
