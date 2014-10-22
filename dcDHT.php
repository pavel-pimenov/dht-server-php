<?php

namespace Flylink\DHT;

/**
 * DHT server.
 *
 * @author SergeyAS <sa.stolper@gmail.com>
 * @author JhaoDa   <jhaoda@gmail.com>
 */
class DhtServer {
    const VERSION = '2.0.2';

    const MODE_ADD    = 1;
    const MODE_PING   = 2;
    const MODE_REMOVE = 3;

    /**
     * @type DB
     */
    private $db;

    private $cid            = null;
    private $port           = 6250;
    private $host           = null;
    private $useCompression = 0;
    private $userAgent      = null;
    private $mode           = self::MODE_ADD;

    public function __construct(array $dbOptions) {
        $this->db = new DB($dbOptions);
    }

    public function run() {
        $this->parseRequest($_GET, $_SERVER);

        if (self::MODE_PING == $this->mode) {
            $this->db->execute('UPDATE {table} SET live = 1 WHERE cid=:cid AND ip=:ip', [
                ':cid' => $this->cid, ':ip' => $this->host
            ]);

            die('Live OK!');
        }

        if (self::MODE_REMOVE == $this->mode) {
            $this->db->execute('DELETE FROM {table} WHERE cid=:cid AND ip=:ip', [
                ':cid' => $this->cid, ':ip' => $this->host
            ]);

            die('Shutdown OK!');
        }

        if (self::MODE_ADD == $this->mode) {
            $this->db->execute('INSERT INTO {table} (cid, ip, port, user_agent)'.
                ' VALUES (:cid, :ip, :port, :ua) ON DUPLICATE KEY UPDATE conn_count = conn_count + 1', [
                    ':cid' => $this->cid, ':ip' => $this->host, ':port' => $this->port, ':ua' => $this->userAgent
                ]);

            $this->display($this->makeResponse());
        }
    }

    private function parseRequest(array $get = [], array $server = []) {
        if (empty($get) || empty($server)) {
            $this->terminate(500, 'Empty request');
        }

        // CID exist?
        if (isset($get['cid'])) {
            $this->cid = $get['cid'];

            if (39 !== strlen($this->cid)) {
                $this->terminate(400, 'Invalid CID');
            }
        }

        // Use compression?
        if (isset($get['encryption'])) {
            $this->useCompression = intval($get['encryption']);
        }

        // UDP port
        if (isset($get['u4'])) {
            $this->port = intval($get['u4']);

            if ($this->port < 1024) {
                $this->terminate(400, 'UDP port must be >= 1024');
            }
        } else {
            $this->terminate(400, 'You client is in passive mode');
        }

        // user agent
        if (isset($server['HTTP_USER_AGENT'])) {
            $this->userAgent = $server['HTTP_USER_AGENT'];

            if ($this->isInvalidUserAgent($this->userAgent)) {
                $this->terminate(400, 'Invalid user agent - update FlylinkDC -> r502 http://www.flylinkdc.ru');
            }
        }

        $this->host = $server['REMOTE_ADDR'];

        if (isset($get['live']) && intval($get['live']) == 1) {
            $this->mode = self::MODE_PING;
        }

        if (isset($get['stop']) && intval($get['stop']) == 1) {
            $this->mode = self::MODE_REMOVE;
        }
    }

    private function makeResponse() {
        $rows = $this->db->query('SELECT cid, ip, port FROM {table} WHERE cid <> :cid AND live = 1 ORDER BY RAND() LIMIT 0, 50', [
            ':cid' => $this->cid
        ]);

        $xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<Nodes>\n";
            foreach ($rows as $row) {
                $xml .= "<Node CID=\"{$row['cid']}\" I4=\"{$row['ip']}\" U4=\"{$row['port']}\" />\n";
            }
        $xml .= '</Nodes>';

        return $xml;
    }

    private function display($response) {
        header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
        // header('Content-type: text/xml');

        if ($this->useCompression) {
        //    ob_start('ob_gzhandler') || ob_start();
        //        echo $response;
        //    ob_end_flush();
        $gz_res = gzcompress($response, 6);
        echo $gz_res;

        } else {
            echo $response;
        }
    }

    private function terminate($code, $message) {
        $header = sprintf('%s %d %s', $_SERVER['SERVER_PROTOCOL'], $code, $message);
        header($header, null, $code);
        die($header);
    }

    private function isInvalidUserAgent($ua) {
        static $badUA = [
            'FlylinkDC++ r501 build 9474',
            'FlylinkDC++ r501-x64 build 9474',
            'FlylinkDC++ r502-beta7 build 9543'
        ];

        if (in_array($ua, $badUA)) {
            return true;
        }

        return false;
    }
}

require_once 'db.php';

$server = new DhtServer(require('config.php'));

$server->run();
