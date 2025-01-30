<?php
require_once "databaseConnection.php";

/**
 * Получает список всех отелей.
 *
 * @return array Массив отелей или пустой массив в случае ошибки.
 */
function getHotels()
{
    $connection = connectToDatabase();
    $hotels = [];

    $sql = "SELECT id, name, description, address, city FROM hotels";
    if ($result = $connection->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $hotels[] = $row;
        }
        $result->free();
    } else {
        // Обработка ошибки, например, запись в лог
        error_log("Error: " . $connection->error);
    }

    disconnectFromDatabase($connection);
    return $hotels;
}

/**
 * Получает информацию об отеле по его ID.
 *
 * @param int $hotelId ID отеля.
 * @return array|null Массив с данными отеля или null, если отель не найден.
 */
function getHotelById($hotelId)
{
    $connection = connectToDatabase();
    $hotel = null;

    $sql = "SELECT id, name, description, address, city FROM hotels WHERE id = ?";
    if ($stmt = $connection->prepare($sql)) {
        $stmt->bind_param("i", $hotelId);
        $stmt->execute();
        $result = $stmt->get_result();
        $hotel = $result->fetch_assoc();
        $stmt->close();
    } else {
        // Обработка ошибки
        error_log("Error: " . $connection->error);
    }

    disconnectFromDatabase($connection);
    return $hotel;
}

/**
 * Получает список номеров в указанном отеле.
 *
 * @param int $hotelId ID отеля.
 * @param string|null $checkin Дата заезда (необязательно).
 * @param string|null $checkout Дата выезда (необязательно).
 * @return array Массив номеров.
 */
function getRoomsByHotelId($hotelId, $checkin = null, $checkout = null)
{
    $connection = connectToDatabase();
    $rooms = [];

    $sql = "SELECT r.id, r.name, r.description, r.price, r.capacity 
            FROM rooms r
            WHERE r.hotel_id = ?";

    if ($checkin !== null && $checkout !== null) {
        $sql .= " AND r.id NOT IN (
                    SELECT b.room_id
                    FROM bookings b
                    WHERE NOT (b.checkout_date <= ? OR b.checkin_date > ?)
                )";
    }

    if ($stmt = $connection->prepare($sql)) {
        if ($checkin !== null && $checkout !== null) {
            $stmt->bind_param("iss", $hotelId, $checkin, $checkout);
        } else {
            $stmt->bind_param("i", $hotelId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        $stmt->close();
    } else {
        // Обработка ошибки
        error_log("Error: " . $connection->error);
    }

    disconnectFromDatabase($connection);
    return $rooms;
}

/**
 * Добавляет бронирование номера.
 *
 * @param int $roomId ID номера.
 * @param string $guestName Имя гостя.
 * @param string $guestEmail Email гостя.
 * @param string $checkin Дата заезда.
 * @param string $checkout Дата выезда.
 * @return bool True в случае успеха, False в случае ошибки.
 */
function addBooking($roomId, $guestName, $guestEmail, $checkin, $checkout)
{
    $connection = connectToDatabase();
    $success = false;

    $sql = "INSERT INTO bookings (room_id, guest_name, guest_email, checkin_date, checkout_date) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $connection->prepare($sql)) {
        $stmt->bind_param("issss", $roomId, $guestName, $guestEmail, $checkin, $checkout);
        if ($stmt->execute()) {
            $success = true;
        } else {
            // Обработка ошибки
            error_log("Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Обработка ошибки
        error_log("Error: " . $connection->error);
    }

    disconnectFromDatabase($connection);
    return $success;
}

/**
 * Получает список бронирований для указанного номера в заданном отеле и временном промежутке.
 *
 * @param int $roomId ID номера.
 * @param string $checkin Дата заезда.
 * @param string $checkout Дата выезда.
 * @return array Массив бронирований.
 */
function getBookingsByRoomId($roomId, $checkin, $checkout)
{
    $connection = connectToDatabase();
    $bookings = [];

    $sql = "SELECT b.id, b.checkin_date, b.checkout_date
            FROM bookings b
            WHERE b.room_id = ?
              AND NOT (b.checkout_date <= ? OR b.checkin_date >= ?)";

    if ($stmt = $connection->prepare($sql)) {
        $stmt->bind_param("iss", $roomId, $checkin, $checkout);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();
    } else {
        error_log("Error: " . $connection->error);
    }

    disconnectFromDatabase($connection);
    return $bookings;
}
/**
 * Получает список доступных номеров в указанном отеле на указанные даты.
 * Добавлены переменные для отслеживания времени выполнения запроса, чтобы можно было посмотреть в дебаггере.
 *
 * @param int $hotelId ID отеля.
 * @param string $checkin Дата заезда.
 * @param string $checkout Дата выезда.
 * @return array|false Массив доступных номеров или false в случае ошибки.
 */
function getAvailableRooms($hotelId, $checkin, $checkout)
{
    $connection = connectToDatabase();
    $rooms = [];

    // Защита от SQL-инъекций
    $hotelId = $connection->real_escape_string($hotelId);
    $checkin = $connection->real_escape_string($checkin);
    $checkout = $connection->real_escape_string($checkout);

    usleep(500000);
    // SQL-запрос для получения доступных номеров
    $sql = "SELECT r.id, r.name, r.price
            FROM rooms r
            WHERE r.hotel_id = '$hotelId'
            AND r.id NOT IN (
                SELECT b.room_id
                FROM bookings b
                WHERE b.room_id IN (SELECT id from rooms WHERE hotel_id = '$hotelId')
                AND (
                    (b.checkin_date <= '$checkin' AND b.checkout_date > '$checkin') OR
                    (b.checkin_date < '$checkout' AND b.checkout_date >= '$checkout') OR
                    (b.checkin_date >= '$checkin' AND b.checkout_date <= '$checkout')
                )
            )";
    $queryStartTime = microtime(true);
    if ($result = $connection->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        $result->free();
        $queryEndTime = microtime(true);
    } else {
        error_log("Error: " . $connection->error);
        return false;
    }


    disconnectFromDatabase($connection);

    return $rooms; // Возвращаем только массив с комнатами
}
