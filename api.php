<?php

require_once "vendor/autoload.php";

use App\BpmnApiHandler;
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Retrieve data from the request
$data = json_decode(file_get_contents('php://input'), true);

// Check required parameters
if (!isset($data['user_id']) || !isset($data['task_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Parameters "user_id" and "task_id" are required.']);
    exit;
}

// Determine the file to work with
if (isset($data['file_content'])) {
    // Work with file content
    $fileName = './uploads/user_' . $data['user_id'] . '_task_' . $data['task_id'] . '.bpmn';
    file_put_contents($fileName, $data['file_content']);

    if (!file_exists($fileName)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save the file from "file_content".']);
        exit;
    }
} elseif (isset($data['fileName'])) {
    // Work with the specified file name
    $fileName = './uploads/' . basename($data['fileName']);
    if (!file_exists($fileName)) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found.']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Either "file_content" or "fileName" must be specified.']);
    exit;
}

try {
    $bpmnApiHandler = new BpmnApiHandler($fileName, $data['user_id'], $data['task_id']);

    // If only the file is provided, return the first step
    if (!isset($data['currentStepId'])) {
        $response = $bpmnApiHandler->getFirstStep();
    } else {
        // If the current step is provided, return its content and the next step
        $response = $bpmnApiHandler->getStepContent($data['currentStepId']);
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    // Delete the temporary file if it was created
    if (isset($data['file_content']) && file_exists($fileName)) {
        unlink($fileName);
    }
}
