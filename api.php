<?php

require_once "vendor/autoload.php";

use App\BpmnApiHandler;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не разрешен. Используйте POST.']);
    exit;
}

// Получение данных из запроса
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['fileName'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Параметр "fileName" обязателен.']);
    exit;
}

$fileName = './uploads/' . basename($data['fileName']);
if (!file_exists($fileName)) {
    http_response_code(404);
    echo json_encode(['error' => 'Файл не найден.']);
    exit;
}

try {
    $bpmnApiHandler = new BpmnApiHandler($fileName);

    // Если передан только файл, возвращаем первый шаг
    if (!isset($data['currentStepId'])) {
        $response = $bpmnApiHandler->getFirstStep();
    } else {
        // Если передан текущий шаг, возвращаем его контент и следующий шаг
        $response = $bpmnApiHandler->getStepContent($data['currentStepId']);
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}