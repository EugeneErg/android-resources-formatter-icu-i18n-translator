<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlPlurals;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlString;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlStringArray;
use RuntimeException;
use SimpleXMLElement;

final readonly class AndroidStringsBuilder
{
    /**
     * @param array<XmlPlurals|XmlString|XmlStringArray> $data
     */
    public function build(array $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');

        foreach ($data as $name => $entry) {
            if (!isset($entry['type'], $entry['value'])) {
                continue;
            }

            $node = $xml->addChild($entry['type']);
            $node->addAttribute('name', $name);
            match (true) {
                $entry instanceof XmlString => $this->appendString($node, $entry),
                $entry instanceof XmlStringArray => $this->appendArray($node, $entry),
                $entry instanceof XmlPlurals => $this->appendPlurals($node, $entry),
            };

            match ($entry['type']) {
                'string' => $this->appendString($node, $entry['value']),
                'plurals' => $this->appendPlurals($node, $entry['value']),
                'string-array' => $this->appendArray($node, $entry['value']),
                default => throw new RuntimeException("Unknown type '{$entry['type']}'"),
            };
        }

        return $xml->asXML();
    }

    private function appendString(SimpleXMLElement $node, XmlString $value): void
    {
        $node[0] = $value->value;
    }

    private function appendPlurals(SimpleXMLElement $node, XmlPlurals $values): void
    {
        foreach ($values->items as $quantity => $text) {
            $item = $node->addChild('item');
            $item->addAttribute('quantity', (string) $quantity);
            $item[0] = $text;
        }
    }

    private function appendArray(SimpleXMLElement $node, XmlStringArray $values): void
    {
        foreach ($values->items as $text) {
            $item = $node->addChild('item');
            $item[0] = $text;
        }
    }
}
