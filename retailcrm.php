<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "https://stage.slavalarionov.store") {
    header('Content-Type: application/json');
    header("Access-Control-Allow-Origin: $http_origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Access-Control-Allow-Credentials: true');
}
require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

header('Content-Type: application/json');

function array_filter_recursive($input) {
    foreach ($input as &$value) {
        if (is_array($value)) {
            $value = array_filter_recursive($value);
        }
    }
    return array_filter($input, function($v) {
        return $v !== null && $v !== '';
    });
}

function clean_properties($properties) {
    return array_values(array_filter($properties, function($prop) {
        return isset($prop['value']) && $prop['value'] !== '' && $prop['value'] !== null;
    }));
}

// Получаем тело запроса
$input = json_decode(file_get_contents('php://input'), true);
$orderData = $input['orderData'] ?? null;

$RETAIL_CRM_API_TOKEN = $_ENV['RETAIL_CRM_API_TOKEN'];
$RETAIL_CRM_API_URL = $_ENV['RETAIL_CRM_API_URL'];

if (!$RETAIL_CRM_API_TOKEN || !$RETAIL_CRM_API_URL) {
    echo json_encode(['error' => 'No retailCRM credentials']);
    exit;
}

if (!$orderData) {
    echo json_encode(['error' => 'No orderData']);
    exit;
}

$order = [
    'number' => $orderData['orderNumber'] ?? null,
    'firstName' => $orderData['receiverFullname'] ?? null,
    'email' => $orderData['email'] ?? null,
    'phone' => $orderData['tel'] ?? null,
    'promoCode' => $orderData['promo']['code'] ?? null,
    'delivery' => [
        'code' => 'cdek-pvz',
        'cost' => $orderData['deliveryPrice'] ?? null,
        'address' => [
            'region' => $orderData['deliveryCity'] ?? null,
            'city' => $orderData['deliveryCity'] ?? null,
            'street' => $orderData['deliveryAddressInfo']['street'] ?? null,
            'building' => $orderData['deliveryAddressInfo']['building'] ?? null,
            'housing' => $orderData['deliveryAddressInfo']['appartament'] ?? null,
            'test' => $orderData['deliveryComment'] ?? null
        ]
    ],
    'items' => [[
        'productName' =>
            ($orderData['strapModel'] ?? '') . ' ' .
            ($orderData['appleWatchModel'] ?? '') . ' ' .
            ($orderData['appleWatchModelSize'] ?? '') . 'мм',
        'quantity' => 1,
        'initialPrice' => $orderData['productsPrice'] ?? null,
        'properties' => [
            ['name' => 'Цвет кожи', 'value' => $orderData['strapLeatherColor'] ?? 'Не выбран'],
            ['name' => 'Цвет строчки', 'value' => $orderData['stitchingColor'] ?? 'Не выбран'],
            ['name' => 'Цвет края', 'value' => $orderData['edgeColor'] ?? 'Не выбран'],
            ['name' => 'Цвет пряжки', 'value' => $orderData['buckleColor'] ?? 'Не выбран'],
            ['name' => 'Цвет адаптера', 'value' => $orderData['adapterColor'] ?? 'Не выбран'],
            ['name' => 'Инициалы', 'value' => !empty($orderData['initials']['choosen']) ? $orderData['initials']['text'] : 'Нет'],
            ['name' => 'Подарочная коробка', 'value' => !empty($orderData['presentBox']['choosen']) ? 'Да' : 'Нет'],
            ['name' => 'Открытка', 'value' => !empty($orderData['postCard']['choosen']) ? $orderData['postCard']['text'] : 'Нет'],
            ['name' => 'Бабочка', 'value' => !empty($orderData['buckleButterfly']['choosen']) ? 'Да' : 'Нет'],
            ['name' => 'Комментарий к доставке', 'value' => $orderData['deliveryComment'] ?? ''],
            ['name' => 'Промокод', 'value' => $orderData['promo']['code'] ?? 'Нет']
        ]
    ]],
    'summ' => $orderData['totalPrice'] ?? null
];

// Очищаем свойства от пустых значений
$order['items'][0]['properties'] = clean_properties($order['items'][0]['properties']);
// Очищаем весь заказ от пустых и null
$order = array_filter_recursive($order);

$params = [
    'apiKey' => $RETAIL_CRM_API_TOKEN,
    'order' => json_encode($order, JSON_UNESCAPED_UNICODE),
];

$ch = curl_init($RETAIL_CRM_API_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode(['error' => curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true]);
} else {
    $resp = json_decode($response, true);
    echo json_encode([
        'error' => $resp['errorMsg'] ?? $response ?? 'Ошибка сервера RetailCRM'
    ]);
}