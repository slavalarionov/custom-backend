<?php
$http_origin = $_SERVER['HTTP_ORIGIN'];
if ($http_origin == "https://stage.slavalarionov.store")
    {
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

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['msgContent'] ?? null;

$TELEGRAM_BOT_TOKEN = $_ENV['TELEGRAM_BOT_TOKEN'];
$TELEGRAM_CHAT_ID = $_ENV['TELEGRAM_CHAT_ID'];

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
     return
         "<b>1.Данные заказа:</b>\n" .
         "Номер заказа: {$order['orderNumber']}\n" .
         "Модель ремешка: {$order['strapModel']}\n" .
         "Цвет кожи: {$order['strapLeatherColor']}\n" .
         "Модель apple watch: {$order['appleWatchModel']}\n" .
         "Размер корпуса Apple Watch: {$order['appleWatchModelSize']}\n" .
         "Цвет корпуса apple watch: {$order['appleWatchModelColor']}\n" .
         "Цвет строчки: {$order['stitchingColor']}\n" .
         "Цвет края: {$order['edgeColor']}\n" .
         "цвет пряжки (застежки): {$order['buckleColor']}\n" .
         "Цвет адаптеров (крепление к часам): {$order['adapterColor']}\n" .
         (!empty($order['buckleButterfly']['available']) ? "Вид пряжки: " . ($order['buckleButterfly']['choosen'] ? "Пряжка бабочка" : "Стандартная") . "\n" : "") .
         "Нужны инициалы?: " . ($order['initials']['choosen'] ? "Да, ({$order['initials']['text']})" : "Нет") . "\n" .
         "Нужна подарочная упаковка?: " . ($order['presentBox']['choosen'] ? "Да" : "Нет") . "\n" .
         "Нужна открытка?: " . ($order['postCard']['choosen'] ? "Да, ({$order['postCard']['text']})" : "Нет") . "\n" .
         "\n<b>2.Данные клиента:</b>\n" .
         "ФИО: {$order['receiverFullname']}\n" .
         "Email: {$order['email']}\n" .
         "Номер телефона: {$order['tel']}\n" .
         "\n<b>3.Доставка:</b>\n" .
         "Город доставки: {$order['deliveryCity']}\n" .
         "Способ доставки: {$order['deliveryType']}\n" .
         (
             in_array($order['deliveryType'], ['СДЭК до пункта выдачи', 'Постамат OmniCDEK']) && !empty($order['deliveryPoint'])
                 ? "Название пункта выдачи: {$order['deliveryPoint']['name']}\n" .
                   "Адрес пункта выдачи: {$order['deliveryPoint']['address']}\n" .
                   "Время работы пункта выдачи: {$order['deliveryPoint']['workTime']}\n" .
                   "Телефон пункта выдачи: {$order['deliveryPoint']['phone']}\n"
                 : ($order['deliveryType'] === 'СДЭК курьером до двери' && !empty($order['deliveryAddressInfo'])
                     ? "Улица: {$order['deliveryAddressInfo']['street']}\n" .
                       "Дом: {$order['deliveryAddressInfo']['building']}\n" .
                       "Квартира: {$order['deliveryAddressInfo']['appartament']}\n"
                     : ($order['deliveryType'] === 'Почта России 1 класс'
                         ? "Адрес: {$order['mailAddress']}\n"
                         : ($order['deliveryType'] === 'Доставка курьером по Санкт-Петербургу'
                             ? "Адрес: {$order['curierAddress']}\n"
                             : ""
                         )
                     )
                 )
         ) .
         (!empty($order['deliveryComment']) ? "Комментарий к заказу: {$order['deliveryComment']}\n" : "") .
         "\n<b>4.Стоимость и оплата:</b>\n" .
         "Стоимость товара: {$order['productsPrice']} руб\n" .
         (!empty($order['additionalOptionsPrice']) ? "Стоимость доп опций: {$order['additionalOptionsPrice']} руб\n" : "") .
         "Стоимость доставки: " . ($order['deliveryPrice'] ?? 0) . " руб\n" .
         (!empty($order['promo']['used']) ? "Скидка на товар по промокоду: {$order['promo']['discountValueFull']}\n" : "") .
         "Использованный промокод: {$order['promo']['code']}\n" .
         "Итоговая сумма: {$order['totalPrice']} руб\n" .
         "Способ оплаты: {$order['paymentType']}\n";
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