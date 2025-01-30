<?php

file_put_contents('api_log.txt', '=== Начало запроса ===' . PHP_EOL, FILE_APPEND);
file_put_contents('api_log.txt', '$_REQUEST: ' . print_r($_REQUEST, true) . PHP_EOL, FILE_APPEND);
file_put_contents('api_log.txt', '$_POST: ' . print_r($_POST, true) . PHP_EOL, FILE_APPEND);

require_once 'queries.php';
session_start();

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'getRooms':
            $hotelId = $_POST['hotel'] ?? null;
            $checkin = $_POST['checkin'] ?? null;
            $checkout = $_POST['checkout'] ?? null;
            if (empty($hotelId) || empty($checkin) || empty($checkout)) {
                throw new Exception('Не заполнены все поля формы.');
            }

            if (!is_numeric($hotelId) || $hotelId < 0) {
                throw new Exception("Некорректный ID отеля.");
            }

            $checkinDate = DateTime::createFromFormat('Y-m-d', $checkin);
            $checkoutDate = DateTime::createFromFormat('Y-m-d', $checkout);

            if (!$checkinDate || !$checkoutDate) {
                throw new Exception("Некорректный формат даты.");
            }

            $rooms = getRoomsByHotelId($hotelId, $checkin, $checkout);

            header('Content-Type: application/json');
            echo json_encode($rooms);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
