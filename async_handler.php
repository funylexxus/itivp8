<?php
require_once 'vendor/autoload.php';
require_once 'queries.php';

use React\EventLoop\Factory;
use React\Http\Browser;

$loop = Factory::create();
$client = new Browser($loop);

$selectedHotel = $_POST['hotel'] ?? null;
$checkin = $_POST['checkin'] ?? null;
$checkout = $_POST['checkout'] ?? null;
$result = ['success' => false, 'rooms' => [], 'error' => null];

if (empty($selectedHotel) || empty($checkin) || empty($checkout)) {
    $result['error'] = 'Пожалуйста, заполните все поля формы.';
} else {


    $formData = [
        'action' => 'getRooms',
        'hotel' => $selectedHotel,
        'checkin' => $checkin,
        'checkout' => $checkout,
    ];

    $client->post(
        'http://localhost:80/sanboy7/api.php',
        ['Content-Type' => 'application/x-www-form-urlencoded'],
        http_build_query($formData)
    )->then(
        function (Psr\Http\Message\ResponseInterface $response) use (&$result) {
            $body = (string)$response->getBody();
            $rooms = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['success'] = true;
                $result['rooms'] = $rooms;
            } else {
                $result['error'] = 'Ошибка декодирования JSON: ' . json_last_error_msg();
            }
        },
        function (Exception $exception) use (&$result) {
            $result['error'] = $exception->getMessage();
        }
    );
}

$loop->run();

header('Content-Type: application/json');
echo json_encode($result);
exit;
