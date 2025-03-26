<?php

use App\CustomParticipant;
use PHPMentors\Workflower\Definition\Bpmn2Reader;

require_once "vendor/autoload.php";

$bpmn2Reader = new Bpmn2Reader();
//$workflow = $bpmn2Reader->read('vendor/phpmentors/workflower/tests/Resources/config/workflower/LoanRequestProcess.bpmn');
$workflow = $bpmn2Reader->read('c.bpmn');
$participant = new CustomParticipant(['ROLE_BRANCH', 'ROLE_CREDIT_FACTORY', 'ROLE_BACK_OFFICE', '__ROLE__']);



$workflow->setProcessData(array('rejected' => false));
$workflow->start($workflow->getFlowObject('Start'));

$currentFlowObject = $workflow->getCurrentFlowObject();
echo $currentFlowObject->getName().'<br>';

$workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
$workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
$workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

$currentFlowObject = $workflow->getCurrentFlowObject();
echo $currentFlowObject->getName().'<br>';


$workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
$workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);
$workflow->setProcessData(array('rejected' => false));

$workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

$currentFlowObject = $workflow->getCurrentFlowObject();
echo $currentFlowObject->getName().'<br>';

$workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
$workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);

$workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

$currentFlowObject = $workflow->getCurrentFlowObject();
echo $currentFlowObject->getName().'<br>';

$workflow->allocateWorkItem($workflow->getCurrentFlowObject(), $participant);
$workflow->startWorkItem($workflow->getCurrentFlowObject(), $participant);

$workflow->completeWorkItem($workflow->getCurrentFlowObject(), $participant);

$currentFlowObject = $workflow->getCurrentFlowObject();
echo $currentFlowObject->getName().'<br>';
