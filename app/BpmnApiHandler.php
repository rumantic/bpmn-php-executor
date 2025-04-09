<?php

namespace App;

use PHPMentors\Workflower\Definition\Bpmn2Reader;

class BpmnApiHandler
{
    private $bpmnFilePath;
    private $workflow;
    private $xmlLoader;

    public function __construct(string $bpmnFilePath)
    {
        if (!file_exists($bpmnFilePath)) {
            throw new \Exception("Файл BPMN не найден: " . $bpmnFilePath);
        }

        $this->bpmnFilePath = $bpmnFilePath;
        $this->xmlLoader = new XMLContentLoader($bpmnFilePath);

        $bpmn2Reader = new Bpmn2Reader();
        if ( isset($_SESSION['workflow']) ) {
            $this->workflow = unserialize($_SESSION['workflow']);
        } else {
            $this->workflow = $bpmn2Reader->read($bpmnFilePath);
        }
    }

    /**
     * Получить первый шаг и ID следующего шага.
     *
     * @return array
     * @throws \Exception
     */
    public function getFirstStep(): array
    {
        $startVariants = ['Start', 'StartEvent', 'StartEvent_1'];
        foreach ($startVariants as $startVariant) {
            $startEvent = $this->workflow->getFlowObject($startVariant);
            if ($startEvent !== null) {
                break;
            }
        }

        if ($startEvent === null) {
            throw new \Exception("Ошибка: StartEvent не найден в BPMN-файле.");
        }

        $participant = new CustomParticipant(['ROLE_BRANCH', 'ROLE_CREDIT_FACTORY', 'ROLE_BACK_OFFICE', '__ROLE__']);
        $this->workflow->start($startEvent);

        // Обновление текущего шага
        $currentFlowObject = $this->workflow->getCurrentFlowObject();


        $nextStep = $currentFlowObject->getId();
        $_SESSION['workflow'] = serialize($this->workflow);

        return [
            'currentStepId' => $startEvent->getId(),
            'currentStepContent' => $this->xmlLoader->getHtmlContent($startEvent->getId()),
            'nextStepId' => $nextStep,
        ];
    }

    /**
     * Получить контент текущего шага и ID следующего шага.
     *
     * @param string $currentStepId
     * @return array
     * @throws \Exception
     */
    public function getStepContent($currentStepId)
    {
        $currentStep = $this->workflow->getFlowObject($currentStepId);


        if ($currentStep === null) {
            throw new \Exception("Ошибка: Шаг с ID '$currentStepId' не найден в BPMN-файле.");
        }

        $participant = new CustomParticipant(['ROLE_BRANCH', 'ROLE_CREDIT_FACTORY', 'ROLE_BACK_OFFICE', '__ROLE__']);

        $this->workflow->allocateWorkItem($currentStep, $participant);
        $this->workflow->startWorkItem($currentStep, $participant);
        $this->workflow->completeWorkItem($currentStep, $participant);

        // Обновление текущего шага
        $currentFlowObject = $this->workflow->getCurrentFlowObject();




        if (empty($currentFlowObject)) {
            return [
                'currentStepId' => $currentStepId,
                'currentStepContent' => $this->xmlLoader->getHtmlContent($currentStepId),
                'message' => 'Процесс завершен.',
            ];
        }

        $nextStep = $currentFlowObject->getId();

        $_SESSION['workflow'] = serialize($this->workflow);

        return [
            'currentStepId' => $currentStepId,
            'currentStepContent' => $this->xmlLoader->getHtmlContent($currentStepId),
            'nextStepId' => $nextStep->getId(),
        ];
    }
}
