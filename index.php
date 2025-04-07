<?php

use App\CustomParticipant;
use PHPMentors\Workflower\Definition\Bpmn2Reader;

session_start();
require_once "vendor/autoload.php";

// Загрузка BPMN-файла
$bpmn2Reader = new Bpmn2Reader();
// $workflow = $bpmn2Reader->read('c.bpmn');
$workflow = $bpmn2Reader->read('long-simple.bpmn');

// Инициализация участника
$participant = new CustomParticipant(['ROLE_BRANCH', 'ROLE_CREDIT_FACTORY', 'ROLE_BACK_OFFICE', '__ROLE__']);

// Проверка текущего шага в сессии
if (!isset($_SESSION['currentStep'])) {
    // Получение идентификатора StartEvent
    $startEvent = $workflow->getFlowObject('StartEvent_1'); // Замените 'Start' на реальный ID из BPMN-файла
    if ($startEvent === null) {
        die("Ошибка: StartEvent с идентификатором 'Start' не найден в BPMN-файле.");
    }

    $workflow->start($startEvent);
    $_SESSION['workflow'] = serialize($workflow);
    $_SESSION['currentStep'] = $workflow->getCurrentFlowObject()->getId();
} else {
    $workflow = unserialize($_SESSION['workflow']);
    $workflow->setProcessData(['rejected' => $_SESSION['rejected'] ?? false]);
}

// Получение текущего шага
$currentFlowObject = $workflow->getFlowObject($_SESSION['currentStep']);

// Если это условный переход, обработать выбор пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choice'])) {
    $_SESSION['rejected'] = ($_POST['choice'] === 'yes');
    $workflow->setProcessData(['rejected' => $_SESSION['rejected']]);
}

// Выполнение текущего шага
if (!$workflow->isEnded()) {
    $workflow->allocateWorkItem($currentFlowObject, $participant);
    $workflow->startWorkItem($currentFlowObject, $participant);
    $workflow->completeWorkItem($currentFlowObject, $participant);

    // Обновление текущего шага
    $currentFlowObject = $workflow->getCurrentFlowObject();
    $_SESSION['currentStep'] = $currentFlowObject->getId();
    $_SESSION['workflow'] = serialize($workflow);
}

// Генерация HTML-страницы
if ($workflow->isEnded()) {
    echo "<h1>Процесс завершен!</h1>";
    session_destroy();
} else {
    echo "<h1>Текущий шаг: " . htmlspecialchars($currentFlowObject->getName()) . "</h1>";

    // Если это условный переход, показать выбор
    if ($currentFlowObject instanceof \PHPMentors\Workflower\Workflow\Gateway\ExclusiveGateway) {
        echo "<form method='POST'>";
        echo "<p>Выберите действие:</p>";
        echo "<button type='submit' name='choice' value='yes'>Да</button>";
        echo "<button type='submit' name='choice' value='no'>Нет</button>";
        echo "</form>";
    } else {
        echo "<form method='POST'>";
        echo "<button type='submit'>Далее</button>";
        echo "</form>";
    }
}
