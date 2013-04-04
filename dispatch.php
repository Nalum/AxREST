<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', true);

require 'Tonic/Autoloader.php';

$services_json = json_decode(getenv("VCAP_SERVICES"),true);
$mysql_config = $services_json["mysql-5.1"][0]["credentials"];

define('PDO_CONN_STRING', 'mysql:host=' . $mysql_config["hostname"] . ';port=' . $mysql_config["port"] . ';dbname=' . $mysql_config["name"] . ';');
define('PDO_CONN_USER', $mysql_config["username"]);
define('PDO_CONN_PASS', $mysql_config["password"]);

$config = array(
    'load' => array(
        './AxREST/*.php',
    )
);

$app = new Tonic\Application($config);
$request = new Tonic\Request();

try {
    $resource = $app->getResource($request);
    $response = $resource->exec();
} catch (Tonic\NotFoundException $e) {
    $response = new Tonic\Response(404, $e->getMessage());
} catch (Tonic\UnauthorizedException $e) {
    $response = new Tonic\Response(401, $e->getMessage());
    $response->wwwAuthenticate = 'Basic realm="My Realm"';
} catch (Tonic\Exception $e) {
    $response = new Tonic\Response($e->getCode(), $e->getMessage());
}

$response->output();