<?php
require_once 'queries.php';
session_start();

$hotels = getHotels();
$selectedHotel = $_POST['hotel'] ?? null;
$checkin = $_POST['checkin'] ?? null;
$checkout = $_POST['checkout'] ?? null;
$rooms = [];
$errorMessage = null;

if (isset($_POST['load-rooms'])) {

    $startTime = microtime(true);

    $result = json_decode(file_get_contents('http://localhost:80/sanboy7/async_handler.php', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($_POST)
        ]
    ])), true);

    $endTime = microtime(true);
    $executionTime = $endTime - $startTime;
    echo "";

    if ($result['success']) {
        $rooms = $result['rooms'];
    } else {
        $errorMessage = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Бронирование отелей</title>
    <link rel="stylesheet" href="dist/css/styles.min.css?v=1.1">
    <link rel="stylesheet" href="dist/css/styles.css">
</head>

<body>
    <div class="container">
        <?php if (isset($_SESSION['error'])) : ?>
            <div class="error-message">
                <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])) : ?>
            <div class="success-message">
                <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if ($errorMessage) : ?>
            <div class="error-message">
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>
        <h1>Booking HOtElS</h1>
        <form method="POST">
            <div class="form-group">
                <label for="hotel">Отель:</label>
                <select id="hotel" name="hotel">
                    <?php foreach ($hotels as $hotel) : ?>
                        <option value="<?= $hotel['id'] ?>" <?= $selectedHotel == $hotel['id'] ? 'selected' : '' ?>>
                            <?= $hotel['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="date-input-container">
                <label for="checkin">Дата заезда:</label>
                <input type="date" id="checkin" name="checkin" value="<?= $checkin ?>" class="date-input">
                <i class="icon-calendar"></i>
            </div>
            <div class="date-input-container">
                <label for="checkout">Дата выезда:</label>
                <input type="date" id="checkout" name="checkout" value="<?= $checkout ?>" class="date-input">
                <i class="icon-calendar"></i>
            </div>
            <button type="submit" name="load-rooms">Найти номера</button>
        </form>

        <?php if (!empty($rooms)) : ?>
            <div id="results">
                <h2>Доступные номера:</h2>
                <div id="rooms">
                    <?php foreach ($rooms as $room) : ?>
                        <div class="room">
                            <h3><?= $room['name'] ?></h3>
                            <p>Цена: <?= $room['price'] ?> руб.</p>
                            <form method="POST" action="book.php">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <input type="hidden" name="hotel" value="<?= $selectedHotel ?>">
                                <input type="hidden" name="checkin" value="<?= $checkin ?>">
                                <input type="hidden" name="checkout" value="<?= $checkout ?>">
                                <button type="submit" class="book-button">Забронировать</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php elseif (isset($_POST['load-rooms']) && empty($rooms)): ?>
            <div id="results">
                <p>Нет доступных номеров на выбранные даты.</p>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>