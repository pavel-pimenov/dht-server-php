<?php
/*
  Скрипт DHT сервиса для программы FlylinkDC++ 
 Форма запроса:


 http://<сайт>dcDHT.php?cid=ZXO4VT7KPNYLJBLFLOR5YP3A33SPNOHYMEDJ4MY&encryption=1&u4=6250
 где
 - cid - CID пользователя
 - encryption - использовать криптование 1/0
 - u4 - udp порт для DHT
 

CREATE TABLE  `ssa_inua`.`dhtInfo` (
	`cid` VARCHAR( 39 ) NOT NULL,
	`ip` VARCHAR( 15 ) NOT NULL,
	`udp` INT( 6 ) NOT NULL,
	`dht_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`con_count` INT UNSIGNED ZEROFILL NOT NULL,
	`user_agent` VARCHAR( 256 ) NOT NULL,
	`dht_time` DATETIME
) ENGINE = MYISAM;

 Автор: SergeyAS (12.02.12) sa.stolper@gmail.com

*/

// Настройка
$scriptver = "12.02.2011"; // Версия

$data_host = "localhost";
$data_database = "xxxxxx";
$data_login = "xxxxxx";
$data_password = "xxxxxx";
$data_table = "xxxxxx";


// Дальше лучше ничего не трогать лишний раз
ignore_user_abort(1); // Продолжать работу скрипта, даже если это никому не надо

// check arguments
  if(strlen($cid = $_GET['cid']) != 39)
  {
    $error = "HTTP/1.1 400 Invalid CID.";
    header($error);
    die($error);
  }


// Use ZIP compression?
if (!isset($_GET["encryption"])) {
	$enc = 0;
} else {
	$enc = $_GET["encryption"];
}

// UDPPort
if (!isset($_GET["u4"])) {
	$udp = 6250;
} else {
	$udp = $_GET["u4"];
}

if ($udp == 0){
    $udp=6250;
}


if (!isset($_SERVER['HTTP_USER_AGENT'])) {
	$userAgent = "";
} else {
	$userAgent = $_SERVER['HTTP_USER_AGENT'];
}

// Придумаем переменную, если ее нет. Совместимость между версиями PHP (?)
if (!isset($PHP_SELF)){
	$PHP_SELF = basename($_SERVER["PHP_SELF"]);
}

$host = $_SERVER['REMOTE_ADDR'];

//header('Content-type: text/html charset=utf-8'); Вызывает жуткие непонятки у IE7, возможно и 6 (нечем потестить)
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Дата из прошлого


if (!$cid || !$host ) die("Sorry, but you are not authorized to view this webpage");


$connect = mysql_pconnect($data_host, $data_login, $data_password) or die(mysql_error());
$select_db = mysql_select_db($data_database, $connect);

//[+]PPA 06.06.2014
if (isset($_GET["stop"]))
{
 $query  = "delete FROM $data_table WHERE cid = '$cid' and ip = '$host'";
 $result = mysql_query($query, $connect) or die(mysql_error());
 mysql_close($connect);
 die("Shutdown OK!");
}

$query = " SELECT dht_id, con_count FROM $data_table WHERE cid = '$cid'";
$result = mysql_query($query, $connect) or die(mysql_error());
$rows = mysql_fetch_array($result, MYSQL_BOTH);

if (!$rows){
	$query = " INSERT INTO $data_table (cid, ip, udp, con_count, user_agent, dht_time) VALUES
	         ('$cid', '$host', $udp, 1, '$userAgent', NOW()) ";
	$result = mysql_query($query, $connect) or die(mysql_error());
} else {

        $conCount = $rows['con_count'] + 1;
	$dht_id = $rows['dht_id'];
	$query = " UPDATE $data_table SET ip = '$host', udp = $udp, con_count = $conCount, user_agent = '$userAgent', dht_time = NOW() WHERE dht_id = $dht_id";
	$result = mysql_query($query, $connect) or die(mysql_error());
}


$xml_output_buffer = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<Nodes>\n"; // буфер для создания xml'я


/*
$offset_result = mysql_query( " SELECT FLOOR(RAND() * COUNT(*)) AS `offset` FROM $data_table ") or die(mysql_error());
$offset_row = mysql_fetch_object( $offset_result );
$offset = $offset_row->offset;
$result = mysql_query( " SELECT cid, ip, udp FROM $data_table  WHERE  cid != '$cid' LIMIT $offset, 50 " ) or die(mysql_error());
*/


$query = " SELECT cid, ip, udp, RAND() as 'R' FROM $data_table WHERE cid != '$cid' ORDER BY 'R' LIMIT 0, 50";
$result = mysql_query($query, $connect) or die(mysql_error());

while ($rows = mysql_fetch_array($result, MYSQL_BOTH)){
      $res_cid = $rows['cid'];
      $res_ip = $rows['ip'];
      $res_udp = $rows['udp'];

      $xml_output_buffer .= "<Node CID = \"$res_cid\" I4=\"$res_ip\" U4=\"$res_udp\" />\n";
}

/*
*/

mysql_close($connect);

$xml_output_buffer .= "</Nodes>";
ob_end_clean();
if ($enc) {
	$xml_output_buffer = gzcompress($xml_output_buffer, 9); 
}

die($xml_output_buffer);

php?>
