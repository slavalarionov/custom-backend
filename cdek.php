<?php

$allowed_origins = [
    'http://95.163.242.84:3000',
    'https://slavalarionov.store'
    'https://stage.slavalarionov.store'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'vendor/autoload.php';
use CdekSDK\Requests;
/* $client = new \CdekSDK\CdekClient('EMscd6r9JnFiQ3bLoyjJY6eM78JrJceI', 'PjLZkKBHEiLK3YsjtNrt3TGNG0ahs3kG', new \GuzzleHttp\Client([
    'base_uri' => 'https://integration.edu.cdek.ru',
])); */
$client = new \CdekSDK\CdekClient('I8UbBqgsHjhGTnwF9s6TYkjallPgQEsd', 'uqiRbucbGsrvFD226Qjn3hTAUSRNcQEC');
$data = file_get_contents('php://input');
$body = json_decode($data);
$type = $body->{'type'};
if($type === 'cities-list' ) {
    $cityName = $body->{'name'};
    $request = new Requests\CitiesRequest();
    $request->setPage(0)->setSize(10)->setCityName($cityName)->setCountryCode('RU');
    
    $response = $client->sendCitiesRequest($request);
    $array = array();
    foreach ($response as $location) {
    /** @var \CdekSDK\Common\Location $location */
    $object = (object) [
        'cityName' => $location->getCityName(),
        'cityCode' => $location->getCityCode(),
        'cityUuid' => $location->getCityUuid(),
        'country' => $location->getCountry(),
        'countryCode' => $location->getCountryCodeISO(),
        'region' => $location->getRegion(),
        'regionCode' => $location->getRegionCode(),
        'subRegion' => $location->getSubRegion(),
        'latitude' => $location->getLatitude(),
        'longitude' => $location->getLongitude(),
        'kladr' => $location->getKladr(),
    ];
    array_push($array, $object);
    // print_r(json_encode($array));
}
print_r(json_encode($array));
} else if($type === 'pvz-list') {
    $cityCode = $body->{'cityCode'};
    $request = new Requests\PvzListRequest();
    $request->setCityId($cityCode);
    $request->setType(Requests\PvzListRequest::TYPE_ALL);
    // $request->setCashless(true);
    // $request->setCash(true);
    // $request->setCodAllowed(true);
    // $request->setDressingRoom(true);

    $response = $client->sendPvzListRequest($request);
    $array = array();
    foreach ($response as $item) {
        /** @var \CdekSDK\Common\Pvz $item */
        $object = (object) [
            'code' => $item->Code,
            'name' => $item->Name,
            'address' => $item->Address,
            'addressComment' => $item->AddressComment,
            'type' => $item->Type,
            'city' => $item->City,
            'cityCode' => $item->CityCode,
            'workTime' => $item->WorkTime,  
            'postalCode' => $item->PostalCode,
            'phone' => $item->Phone,
            'phoneDetails' => $item->phoneDetails,
            'coordX' => $item->coordX,
            'coordY' => $item->coordY,
        ];
        array_push($array, $object);
    }
    print_r(json_encode($array));
} else if($type === 'calc') {
    // для выполнения запроса без авторизации используется
// $request = new Requests\CalculationRequest();
// $request->set...() и так далее
    $cityCode = $body->{'cityCode'};
    $request = new Requests\CalculationWithTariffListAuthorizedRequest();
        $request->setSenderCityId(137)
        ->setReceiverCityId($cityCode)
            ->addTariffToList(136)
            ->addTariffToList(137)
            ->addPackage([
                'weight' => 0.5,
                'length' => 0.2,
                'width'  => 0.1,
                'height' => 0.1,
            ]);
    
        $response = $client->sendCalculationWithTariffListRequest($request);
        // echo $response->hasErrors();
        $calcArr = array();
        foreach ($response->getResults() as $result) {
            if ($result->hasErrors()) {
                // обработка ошибок
        
                continue;
            }
            $resItem = (object) [
                'price' => $result->getPrice(),
                'minDays' => $result->getDeliveryPeriodMin(),
                'tariffId' =>   $result->getTariffId(),
            ];
            if (!$result->getStatus()) {
                continue;
            }
            array_push($calcArr, $resItem);
        }
        print_r(json_encode($calcArr));
}
?>