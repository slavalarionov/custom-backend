<?php 
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
require_once 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$data = file_get_contents('php://input');
$body = json_decode($data);
$type = $body->{'type'};
$token = $_ENV['DADATA_API_KEY'];
$secret = $_ENV['DADATA_SECRET'];
$dadata = new \Dadata\DadataClient($token, $secret);
if ($type === 'street') {
    $address = $body->{'address'};
    $query = $body->{'query'};
    $cityName = $body->{'cityName'};
    $locations = [[ "city" => $cityName ]];
    $fromBounds = [ "value" => "street" ];
    $toBounds = [ "value" => "street" ];
    $result = $dadata->suggest("address", $query, 10, ["locations" => $locations, "from_bound" => $fromBounds, "to_bound" => $toBounds]);
    print_r(json_encode($result));
} else if( $type === "building") { 
    $streetFiasId = $body->{"streetFiasId"};
    $query = $body->{'query'};
    $locations = [[ "street_fias_id" => $streetFiasId ]];
    $fromBounds = [ "value" => "house" ];
    $toBounds = [ "value" => "house" ];
    $result = $dadata->suggest("address", $query, 10, ["locations" => $locations, "from_bound" => $fromBounds, "to_bound" => $toBounds]);
    print_r(json_encode($result));
 }