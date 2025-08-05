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

require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
// api/retailcrm.php

header('Content-Type: application/json');

// Получаем тело запроса
$input = json_decode(file_get_contents('php://input'), true);
$orderData = $input['orderData'] ?? null;

// Проверяем наличие необходимых переменных окружения
$RETAIL_CRM_API_TOKEN = getenv('RETAIL_CRM_API_TOKEN');
$RETAIL_CRM_API_URL = getenv('RETAIL_CRM_API_URL');

if (!$RETAIL_CRM_API_TOKEN || !$RETAIL_CRM_API_URL) {
    echo json_encode(['error' => 'No retailCRM credentials']);
    exit;
}

if (!$orderData) {
    echo json_encode(['error' => 'No orderData']);
    exit;
}

// Формируем объект заказа
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
        'initialPrice' => $orderData['totalPrice'] ?? null,
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

// Готовим параметры для POST-запроса
$params = [
    'apiKey' => $RETAIL_CRM_API_TOKEN,
    'order' => json_encode($order, JSON_UNESCAPED_UNICODE)
];

// Отправляем запрос через cURL
$ch = curl_init($RETAIL_CRM_API_URL);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

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