<?php
namespace App;

class XMLContentLoader
{
    private $simple_xml;

    /**
     * Конструктор загружает XML из файла.
     *
     * @param string $filePath Путь к BPMN-файлу.
     * @throws \Exception Если файл не найден или не удалось загрузить XML.
     */
    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Файл не найден: " . $filePath);
        }

        $this->simple_xml = new \SimpleXMLElement(file_get_contents($filePath));

        if ($this->simple_xml === false) {
            throw new \Exception("Не удалось загрузить XML из файла: " . $filePath);
        }
    }

    /**
     * Получить узел по идентификатору.
     *
     * @param string $id Идентификатор узла.
     * @return \SimpleXMLElement|null Узел или null, если не найден.
     */
    public function getNodeById(string $id): ?\SimpleXMLElement
    {
        $xpath = "//*[@id='$id']";
        $result = $this->simple_xml->xpath($xpath);

        return $result ? $result[0] : null;
    }

    /**
     * Получить значение nc:property с name="htmlContent" для указанного узла.
     *
     * @param string $id Идентификатор узла.
     * @return string Значение htmlContent или пустая строка, если элемент отсутствует или пуст.
     */
    public function getHtmlContent(string $id): string
    {
        $node = $this->getNodeById($id);

        if ($node) {
            $extensionElements = $node->xpath("bpmn2:extensionElements");
            if ($extensionElements && isset($extensionElements[0])) {
                $property = $extensionElements[0]->xpath("nc:property[@name='htmlContent']");
                if ($property && isset($property[0]['value'])) {
                    return (string)$property[0]['value'];
                }
            }
        }

        return '';
    }
}
