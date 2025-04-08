<?php

use App\CustomParticipant;
use PHPMentors\Workflower\Definition\Bpmn2Reader;

session_start();
require_once "vendor/autoload.php";

// Проверка загрузки файла BPMN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bpmnFile']) && $_FILES['bpmnFile']['error'] === UPLOAD_ERR_OK) {
    $uploadedFilePath = __DIR__ . '/uploads/' . basename($_FILES['bpmnFile']['name']);
    $uploadedFilePathL = './uploads/' . basename($_FILES['bpmnFile']['name']);
    move_uploaded_file($_FILES['bpmnFile']['tmp_name'], $uploadedFilePath);
    $_SESSION['bpmnFile'] = $uploadedFilePath;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['useDefault'])) {
    $_SESSION['bpmnFile'] = './uploads/long-simple.bpmn';
} else {
    $uploadedFilePathL = $_SESSION['bpmnFile'];
}

// Если файл BPMN не выбран, показать форму загрузки
if (!isset($_SESSION['bpmnFile'])) {
    echo "<h1>Выберите файл BPMN</h1>";
    echo "<form method='POST' enctype='multipart/form-data'>";
    echo "<p>Загрузите файл BPMN:</p>";
    echo "<input type='file' name='bpmnFile' required>";
    echo "<button type='submit'>Загрузить</button>";
    echo "</form>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='useDefault'>Использовать файл по умолчанию</button>";
    echo "</form>";
    exit;
}
//unset($_SESSION['currentStep']);
//unset($_SESSION['bpmnFile']);
//echo $_SESSION['bpmnFile'];
// Загрузка BPMN-файла
$bpmn2Reader = new Bpmn2Reader();
//$workflow = $bpmn2Reader->read($_SESSION['bpmnFile']);
$workflow = $bpmn2Reader->read($uploadedFilePathL);
$xmlLoader = new \App\XMLContentLoader($_SESSION['bpmnFile']);

// Инициализация участника
$participant = new CustomParticipant(['ROLE_BRANCH', 'ROLE_CREDIT_FACTORY', 'ROLE_BACK_OFFICE', '__ROLE__']);

// Проверка текущего шага в сессии
if (!isset($_SESSION['currentStep'])) {
    $start_variants = ['Start', 'StartEvent', 'StartEvent_1'];
    // Получение идентификатора StartEvent
    foreach ($start_variants as $start_variant) {
        $startEvent = $workflow->getFlowObject($start_variant);
        if ($startEvent != null) {
            break;
        }

    }
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


    $content = $xmlLoader->getHtmlContent($currentFlowObject->getId());
    echo $content;

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
