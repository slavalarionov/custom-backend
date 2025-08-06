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
$method = $_SERVER['REQUEST_METHOD'];
if ($method !== 'POST') {
	exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once 'vendor/autoload.php';
$file = $_FILES['myfile'];

$title = "Заявка";
foreach ($_POST as $key => $value) {
	if ($value != "" && $key != "check" && $key != "policy") {
		$message .= "
		" . (($c = !$c) ? '<tr>' : '<tr style="background-color: #f8f8f8;">') . "
			<td style='padding: 10px; border: #e9e9e9 1px solid;'><b>$key</b></td>
			<td style='padding: 10px; border: #e9e9e9 1px solid;'>$value</td>
		</tr>
		";
	}
}

$body = "<table style='width: 100%;'>$message</table>";

$mail = new PHPMailer();
try {
    $mail->isSMTP();
    $mail->CharSet = "UTF-8";
    $mail->SMTPAuth   = true;
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function($str, $level) {$GLOBALS['status'][] = $str;};

    $mail->Host       = 'ssl://smtp.yandex.ru';
    $mail->Username   = 'hello@slavalarionov.com';
    $mail->Password   = 'bxlpqhzmrbfhxapy';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;
    $mail->setFrom('hello@slavalarionov.com', 'slavalarionov.store');

    $mail->addAddress('hello@slavalarionov.com');


$mail->isHTML(true);
$mail->Subject = $title;
$mail->Body = $body;

if ($mail->send()) {$result = "success";}
else {$result = "error";}

} catch (Exception $e) {
    $result = "error";
    $status = "Сообщение не было отправлено. Причина ошибки: {$mail->ErrorInfo}";
}