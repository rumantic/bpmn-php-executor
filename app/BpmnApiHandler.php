<?php

namespace App;

use PHPMentors\Workflower\Definition\Bpmn2Reader;

class BpmnApiHandler
{
    private string $bpmnFilePath;
    private $workflow;
    private XMLContentLoader $xmlLoader;

    public function __construct(string $bpmnFilePath)
    {
        if (!file_exists($bpmnFilePath)) {
            throw new \Exception("Файл BPMN не найден: " . $bpmnFilePath);
        }

        $this->bpmnFilePath = $bpmnFilePath;
        $this->xmlLoader = new XMLContentLoader($bpmnFilePath);

        $bpmn2Reader = new Bpmn2Reader();
        $this->workflow = $bpmn2Reader->read($bpmnFilePath);
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

        $nextStep = $this->workflow->getFlowObject($startEvent->getOutgoing()[0]->getId());

        return [
            'currentStepId' => $startEvent->getId(),
            'currentStepContent' => $this->xmlLoader->getHtmlContent($startEvent->getId()),
            'nextStepId' => $nextStep->getId(),
        ];
    }

    /**
     * Получить контент текущего шага и ID следующего шага.
     *
     * @param string $currentStepId
     * @return array
     * @throws \Exception
     */
    public function getStepContent(string $currentStepId): array
    {
        $currentStep = $this->workflow->getFlowObject($currentStepId);

        if ($currentStep === null) {
            throw new \Exception("Ошибка: Шаг с ID '$currentStepId' не найден в BPMN-файле.");
        }

        $outgoingFlows = $currentStep->getOutgoing();
        if (empty($outgoingFlows)) {
            return [
                'currentStepId' => $currentStepId,
                'currentStepContent' => $this->xmlLoader->getHtmlContent($currentStepId),
                'message' => 'Процесс завершен.',
            ];
        }

        $nextStep = $this->workflow->getFlowObject($outgoingFlows[0]->getId());

        return [
            'currentStepId' => $currentStepId,
            'currentStepContent' => $this->xmlLoader->getHtmlContent($currentStepId),
            'nextStepId' => $nextStep->getId(),
        ];
    }
}