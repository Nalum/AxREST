<?php
/**
 * Dispatch file.
 *
 * Using the .htaccess file to redirect all traffic to this file
 * if the requested file does not already exist.
 *
 * @author Luke Mallon <mallon.luke@gmail.com>
 */
ini_set('error_reporting', E_ALL ^ E_WARNING);
ini_set('display_errors', true);

// Load the Tonic Autoloader.
require './vendor/autoload.php';

// Get the Database details from the Cloud Service.
$services_json = json_decode(getenv("VCAP_SERVICES"),true);
$mysql_config = $services_json["mysql-5.1"][0]["credentials"];

define('PDO_CONN_STRING', 'mysql:host=' . $mysql_config["hostname"] . ';port=' . $mysql_config["port"] . ';dbname=' . $mysql_config["name"] . ';');
define('PDO_CONN_USER', $mysql_config["username"]);
define('PDO_CONN_PASS', $mysql_config["password"]);

// Set the config for Tonic. Loading in our Resource files.
$config = array(
    'load' => array(
        './src/AxREST/*.php',
    )
);

// Create our applicatoin.
$app = new Tonic\Application($config);
$request = new Tonic\Request();
$json = new stdClass();

// Start the application.
try {
    $resource = $app->getResource($request);
    $response = $resource->exec();
} catch (Tonic\NotFoundException $e) {
    // We could not find the Resource that was requested.
    $json->message = "We were unable to find what you were looking for.";
    $json->error[] = $e->getMessage();
    $response = new Tonic\Response(Tonic\Response::NOTFOUND, json_encode($json));
} catch (Tonic\UnauthorizedException $e) {
    // The request wasnot authorized so cannot access the resource it was looking for.
    $json->message = "You must be authorized to used this service.";
    $json->error[] = $e->getMessage();
    $response = new Tonic\Response(Tonic\Response::UNAUTHORIZED, json_encode($json));
    $response->wwwAuthenticate = 'Basic realm="My Realm"';
} catch (Tonic\Exception $e) {
    // Covers non specific Exceptions thrown in the application.
    $json->message = "There was an error with your request.";
    $json->error[] = $e->getMessage();
    $response = new Tonic\Response($e->getCode(), json_encode($json));
}

// Finally set the content type and output the response to the request.
$response->contentType = "application/json";
$response->output();