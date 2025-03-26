<?php

use PHPMentors\Workflower\Definition\Bpmn2Reader;

require_once "vendor/autoload.php";

$bpmn2Reader = new Bpmn2Reader();

$workflow = $bpmn2Reader->read('vendor/phpmentors/workflower/tests/Resources/config/workflower/LoanRequestProcess.bpmn');
$workflow->start($workflow->getFlowObject('Start'));
$current = $workflow->getCurrentFlowObject();
echo $current->getName().'<br>';
$participant = \Phake::mock('PHPMentors\Workflower\Workflow\Participant\ParticipantInterface');

$workflow->startWorkItem($current, $participant);
$log = $workflow->getActivityLog();
foreach ($log->getIterator() as $logItem) {
    echo '<pre>';
    print_r($logItem);
    echo '</pre>';;
}
echo '<pre>';
print_r($log->toArray());
echo '</pre>';


//echo $current->getRole();
