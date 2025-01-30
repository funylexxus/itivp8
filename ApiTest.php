<?php

use PHPUnit\Framework\TestCase;

require_once 'api.php'; // Подключаем файл api.php
require_once 'queries.php'; // Подключаем файл с функцией getRoomsByHotelId

class ApiTest extends TestCase
{
    /**
     * @covers ::getRoomsByHotelId
     */
    protected function setUp(): void
    {
        // Очищаем сессию перед каждым тестом
        $_SESSION = [];
    }

    public function testGetRoomsActionWithValidData()
    {
        // Подготовка тестовых данных.
        $_POST['action'] = 'getRooms';
        $_POST['hotel'] = 1;
        $_POST['checkin'] = '2024-01-01';
        $_POST['checkout'] = '2024-01-10';

        // Имитация вызова api.php через включение файла.
        ob_start(); // Начинаем буферизацию вывода.
        include 'api.php';
        $output = ob_get_clean(); // Получаем данные из буфера и очищаем его.

        // Проверяем, что вывод является корректным JSON.
        $decodedOutput = json_decode($output, true);
        $this->assertNotNull($decodedOutput, "Output is not valid JSON: " . $output);

        // Проверяем, что ответ не содержит ошибок.
        $this->assertArrayNotHasKey('error', $decodedOutput, "Error in response: " . ($decodedOutput['error'] ?? ''));

        // Дополнительные проверки: проверяем, что вернулся массив комнат.
        $this->assertIsArray($decodedOutput);
    }

    public function testGetRoomsActionWithInvalidHotelId()
    {
        // Подготовка тестовых данных.
        $_POST['action'] = 'getRooms';
        $_POST['hotel'] = 'invalid';
        $_POST['checkin'] = '2023-12-20';
        $_POST['checkout'] = '2023-12-25';

        // Имитация вызова api.php.
        ob_start();
        include 'api.php';
        $output = ob_get_clean();

        $decodedOutput = json_decode($output, true);
        $this->assertNotNull($decodedOutput, "Output is not valid JSON: " . $output);

        // Проверяем, что ответ содержит ошибку.
        $this->assertArrayHasKey('error', $decodedOutput);
        $this->assertEquals(400, http_response_code());
    }

    public function testGetRoomsActionWithInvalidDates()
    {
        $_POST['action'] = 'getRooms';
        $_POST['hotel'] = 1;
        $_POST['checkin'] = 'invalid-date';
        $_POST['checkout'] = '2023-12-25';

        ob_start();
        include 'api.php';
        $output = ob_get_clean();

        $decodedOutput = json_decode($output, true);
        $this->assertNotNull($decodedOutput, "Output is not valid JSON: " . $output);

        $this->assertArrayHasKey('error', $decodedOutput);
        $this->assertEquals(400, http_response_code());
    }


    public function testInvalidAction()
    {
        $_POST['action'] = 'invalidAction';

        ob_start();
        include 'api.php';
        $output = ob_get_clean();

        $decodedOutput = json_decode($output, true);
        $this->assertNotNull($decodedOutput, "Output is not valid JSON: " . $output);

        $this->assertArrayHasKey('error', $decodedOutput);
        $this->assertEquals('Invalid action', $decodedOutput['error']);
        $this->assertEquals(400, http_response_code());
    }
    public function testGetRoomsActionSetsSessionError()
    {
        $_POST['action'] = 'getRooms';
        $_POST['hotel'] = 'invalid';
        $_POST['checkin'] = '2023-07-01';
        $_POST['checkout'] = '2023-07-15';

        ob_start();
        include 'api.php';
        ob_get_clean();

        // Проверяем, что в сессии установлена ошибка
        $this->assertArrayHasKey('error', $_SESSION, "Session error not set");
        $this->assertNotEmpty($_SESSION['error'], "Session error is empty");
        $this->assertEquals(400, http_response_code());
    }
}
