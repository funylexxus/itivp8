<?php
require_once 'queries.php';
session_start();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $roomId = $_POST['room_id'];
        $hotelId = $_POST['hotel'];
        $checkin = $_POST['checkin'];
        $checkout = $_POST['checkout'];
        $guestName = "Гость"; // Временно
        $guestEmail = "test@test.ru"; // Временно

        // Валидация
        if (empty($roomId) || empty($hotelId) || empty($checkin) || empty($checkout)) {
            throw new Exception('Не заполнены все поля формы.');
        }

        if (!is_numeric($roomId) || $roomId < 0) {
            throw new Exception("Некорректный ID номера.");
        }

        if (!is_numeric($hotelId) || $hotelId < 0) {
            throw new Exception("Некорректный ID отеля.");
        }

        $checkinDate = DateTime::createFromFormat('Y-m-d', $checkin);
        $checkoutDate = DateTime::createFromFormat('Y-m-d', $checkout);

        if (!$checkinDate || !$checkoutDate) {
            throw new Exception("Некорректный формат даты.");
        }

        if ($checkinDate == $checkoutDate) {
            throw new Exception("Дата заезда не может совпадать с датой выезда.");
        }

        if ($checkinDate > $checkoutDate) {
            throw new Exception("Дата заезда не может быть позже даты выезда.");
        }

        // Проверка на прошедшее время
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        if ($checkinDate <= $today) {
            throw new Exception("Нельзя бронировать на прошедшую дату.");
        }

        // Проверка: не занят ли номер на эти даты
        $isRoomBooked = getBookingsByRoomId($roomId, $checkin, $checkout);

        if ($isRoomBooked) {
            throw new Exception("Номер уже забронирован на эти даты.");
        }

        if (addBooking($roomId, $guestName, $guestEmail, $checkin, $checkout)) {
            $hotel = getHotelById($hotelId);
            $_SESSION['success'] = "Номер успешно забронирован в отеле {$hotel['name']} с {$checkin} по {$checkout}.";
            header('Location: index.php');
            exit;
        } else {
            throw new Exception('Ошибка при бронировании номера.');
        }
    } else {
        http_response_code(400);
        echo "Некорректный запрос.";
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit;
}
