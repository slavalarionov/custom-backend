<?php
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
// api/sendTelegramMessage.php

header('Content-Type: application/json');

// Получаем тело запроса
$input = json_decode(file_get_contents('php://input'), true);
$order = $input['msgContent'] ?? null;

// Проверяем наличие необходимых переменных окружения
$TELEGRAM_BOT_TOKEN = getenv('TELEGRAM_BOT_TOKEN');
$TELEGRAM_CHAT_ID = getenv('TELEGRAM_CHAT_ID');

if (!$TELEGRAM_BOT_TOKEN || !$TELEGRAM_CHAT_ID) {
    echo json_encode(['error' => 'No telegram credentials']);
    exit;
}
if (!$order) {
    echo json_encode(['error' => 'No order data']);
    exit;
}

// Формируем текст сообщения
function createOrderMessage($order) {
    return trim("
Order #{$order['orderNumber']}
Модель ремешка: {$order['strapModel']}
Цвет кожи: {$order['strapLeatherColor']}
Модель Apple Watch: {$order['appleWatchModel']} ({$order['appleWatchModelSize']}, {$order['appleWatchModelColor']})
Цвет строчки: {$order['stitchingColor']}
Цвет края: {$order['edgeColor']}
Цвет пряжки: {$order['buckleColor']}
Цвет адаптера: {$order['adapterColor']}
Инициалы: " . (!empty($order['initials']['text']) ? $order['initials']['text'] : 'Нет') . "
Подарочная коробка: " . (!empty($order['presentBox']['choosen']) ? 'Да' : 'Нет') . "
Открытка: " . (!empty($order['postCard']['choosen']) ? $order['postCard']['text'] : 'Нет') . "
Бабочка: " . (!empty($order['buckleButterfly']['choosen']) ? 'Да' : 'Нет') . "
Комментарий к доставке: " . ($order['deliveryComment'] ?? '') . "
Промокод: " . (!empty($order['promo']['code']) ? $order['promo']['code'] : 'Нет') . "

Тип доставки: " . ($order['deliveryType'] ?? 'Не указано') . "
Город доставки: " . ($order['deliveryCity'] ?? 'Не указано') . "
Улица: " . ($order['deliveryAddressInfo']['street'] ?? 'Не указано') . "
Дом: " . ($order['deliveryAddressInfo']['building'] ?? 'Не указано') . "
Квартира: " . ($order['deliveryAddressInfo']['appartament'] ?? 'Не указано') . "
Комментарий к заказу: " . ($order['deliveryComment'] ?? 'Не указано') . "
Цена доставки: " . ($order['deliveryPrice'] ?? '') . "
Full name: " . ($order['receiverFullname'] ?? '') . "
Payment Amount: " . (
        !empty($order['deliveryPrice'])
            ? ($order['totalPrice'] + $order['deliveryPrice'])
            : $order['totalPrice']
    ) . " руб.
Payment system: " . ($order['paymentType'] ?? '') . "

Purchaser information:
Email: " . ($order['email'] ?? '') . "
Телефон: " . ($order['tel'] ?? '')
    );
}

$url = "https://api.telegram.org/bot{$TELEGRAM_BOT_TOKEN}/sendMessage";
$message = createOrderMessage($order);

$postData = [
    'chat_id' => $TELEGRAM_CHAT_ID,
    'text' => $message,
    'parse_mode' => 'HTML'
];

// Отправляем запрос через cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
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
    echo json_encode(['error' => 'Ошибка отправки сообщения в Telegram']);
}