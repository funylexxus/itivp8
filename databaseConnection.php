<?php
function connectToDatabase()
{
    try {
        $conn = new mysqli("localhost", "root", "", "hotel_db");
        return $conn;
    } catch (Exception $e) {
        die("Error: " . "ошибка при подключении к бд");
    }
}

function disconnectFromDatabase($conn)
{
    $conn->close();
}
