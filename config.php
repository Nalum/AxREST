<?php
// Get the Database details from the Cloud Service.
$services_json = json_decode(getenv("VCAP_SERVICES"),true);
$mysql_config = $services_json["mysql-5.1"][0]["credentials"];

define('PDO_CONN_STRING', 'mysql:host=' . $mysql_config["hostname"] . ';port=' . $mysql_config["port"] . ';dbname=' . $mysql_config["name"] . ';');
define('PDO_CONN_USER', $mysql_config["username"]);
define('PDO_CONN_PASS', $mysql_config["password"]);