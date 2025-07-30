<?php
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
// api/createOrder.php

header('Content-Type: application/json');

// Получаем тело запроса
$input = json_decode(file_get_contents('php://input'), true);

// Конфигурация (лучше вынести в отдельный файл или использовать .env)
$TOCHKA_CUSTOMER_CODE = $_ENV['TOCHKA_CUSTOMER_CODE'];
$TOCHKA_MERCHANT_ID = $_ENV['TOCHKA_MERCHANT_ID'];
$TOCHKA_TOKEN = $_ENV['TOCHKA_TOKEN'];

// Формируем данные для запроса
$data = [
    'Data' => [
        'customerCode' => $TOCHKA_CUSTOMER_CODE,
        'amount' => $input['amount'] ?? null,
        'purpose' => $input['purpose'] ?? null,
        'paymentMode' => $input['paymentMode'] ?? null,
        'redirectUrl' => $input['redirectUrl'] ?? null,
        'merchantId' => $TOCHKA_MERCHANT_ID
    ]
];

$url = 'https://enter.tochka.com/uapi/acquiring/v1.0/payments';

// Отправляем запрос через cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $TOCHKA_TOKEN,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode([
        'success' => false,
        'message' => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'success' => true,
        'data' => json_decode($response, true)
    ]);
} else {
    echo json_encode([
        'success' => false,
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ]);
}