<?php

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fileName = $_SESSION['fileName'] ?? 'long-simple.bpmn';
    $currentStepId = $_POST['currentStepId'] ?? null;

    echo "<p>Отладка: Начало POST-запроса</p>";
    echo "<p>Текущий файл: $fileName</p>";
    echo "<p>Текущий шаг: " . ($currentStepId ?? 'Не задан') . "</p>";

    // Формируем данные для отправки в API
    $postData = [
        'fileName' => $fileName,
    ];

    if ($currentStepId) {
        $postData['currentStepId'] = $currentStepId;
    }

    echo "<p>Отладка: Данные для отправки в API:</p>";
    echo "<pre>" . htmlspecialchars(json_encode($postData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

    // Отправляем запрос к API
    $ch = curl_init('http://tester/api.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p>Отладка: Ответ от API:</p>";
    echo "<p>HTTP-код ответа: $httpCode</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";

    if ($httpCode !== 200) {
        echo "<h1>Ошибка API</h1>";
        echo "<p>Код ответа: $httpCode</p>";
        echo "<p>Ответ: $response</p>";
        exit;
    }

    $responseData = json_decode($response, true);

    // Проверяем, завершен ли процесс
    if (isset($responseData['message']) && $responseData['message'] === 'Процесс завершен.') {
        echo "<h1>Процесс завершен!</h1>";
        echo "<a href='test_api.php'>Начать заново</a>";
        session_destroy();
        exit;
    }

    // Сохраняем текущий шаг в сессии
    $_SESSION['currentStepId'] = $responseData['nextStepId'] ?? null;
    $_SESSION['currentStepContent'] = $responseData['currentStepContent'] ?? '';
    $_SESSION['currentStepName'] = $responseData['currentStepId'] ?? '';

    echo "<p>Отладка: Сохраненные данные в сессии:</p>";
    echo "<p>Текущий шаг ID: " . ($_SESSION['currentStepId'] ?? 'Не задан') . "</p>";
    echo "<p>Контент текущего шага: " . ($_SESSION['currentStepContent'] ?? 'Пусто') . "</p>";
    echo "<p>Имя текущего шага: " . ($_SESSION['currentStepName'] ?? 'Не задано') . "</p>";
} else {
    // Инициализация сессии
    $_SESSION['fileName'] = 'long-simple.bpmn';
    $_SESSION['currentStepId'] = null;
    $_SESSION['currentStepContent'] = '';
    $_SESSION['currentStepName'] = '';

    echo "<p>Отладка: Инициализация сессии</p>";
    echo "<p>Файл BPMN: " . $_SESSION['fileName'] . "</p>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест API BPMN</title>
</head>
<body>
    <h1>Тест API BPMN</h1>

    <?php if (!empty($_SESSION['currentStepName'])): ?>
        <h2>Текущий шаг: <?= htmlspecialchars($_SESSION['currentStepName']) ?></h2>
        <div>
            <?= $_SESSION['currentStepContent'] ?>
        </div>
        <form method="POST">
            <input type="hidden" name="currentStepId" value="<?= htmlspecialchars($_SESSION['currentStepId']) ?>">
            <button type="submit">Далее</button>
        </form>
    <?php else: ?>
        <form method="POST">
            <button type="submit">Начать процесс</button>
        </form>
    <?php endif; ?>
</body>
</html>
