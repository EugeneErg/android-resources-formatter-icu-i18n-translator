<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlPlurals;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlString;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlStringArray;
use RuntimeException;
use SimpleXMLElement;

final readonly class AndroidStringsParser
{
    /**
     * @return array<XmlPlurals|XmlString|XmlStringArray>
     */
    public function parse(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            throw new RuntimeException('Invalid XML');
        }

        $result = [];

        /** @var SimpleXMLElement $node */
        foreach ($xml->children() as $node) {
            $name = (string) $node['name'];

            if (!$name) {
                continue;
            }

            $result[$name] = match ($node->getName()) {
                'string' => $this->parseString($node),
                'plurals' => $this->parsePlurals($node),
                'string-array' => $this->parseArray($node),
                default => throw new RuntimeException("Unknown tag '{$node->getName()}'"),
            };
        }

        return $result;
    }

    private function parseString(SimpleXMLElement $node): XmlString
    {
        return new XmlString((string) $node);
    }

    private function parsePlurals(SimpleXMLElement $node): XmlPlurals
    {
        $result = [];

        /** @var SimpleXMLElement $item */
        foreach ($node->item as $item) {
            $quantity = (string) $item['quantity'];
            $result[$quantity] = trim((string) $item);
        }

        return new XmlPlurals($result);
    }

    private function parseArray(SimpleXMLElement $node): XmlStringArray
    {
        $result = [];

        /** @var SimpleXMLElement $item */
        foreach ($node->item as $item) {
            $result[] = trim((string) $item);
        }

        return new XmlStringArray($result);
    }
}
