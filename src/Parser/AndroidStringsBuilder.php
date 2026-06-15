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
     * @param array<int|string, XmlPlurals|XmlString|XmlStringArray> $data
     */
    public function build(array $data): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><resources/>');

        foreach ($data as $name => $entry) {
            if ($entry instanceof XmlString) {
                $node = $xml->addChild('string');

                if ($node === null) {
                    throw new RuntimeException("Failed to add child node 'string'");
                }

                $node->addAttribute('name', (string) $name);
                $this->appendString($node, $entry);
            } elseif ($entry instanceof XmlStringArray) {
                $node = $xml->addChild('string-array');

                if ($node === null) {
                    throw new RuntimeException("Failed to add child node 'string-array'");
                }

                $node->addAttribute('name', (string) $name);
                $this->appendArray($node, $entry);
            } elseif ($entry instanceof XmlPlurals) {
                $node = $xml->addChild('plurals');

                if ($node === null) {
                    throw new RuntimeException("Failed to add child node 'plurals'");
                }

                $node->addAttribute('name', (string) $name);
                $this->appendPlurals($node, $entry);
            } else {
                throw new RuntimeException('Unknown entry type');
            }
        }

        $result = $xml->asXML();

        if ($result === false) {
            throw new RuntimeException('Failed to generate XML');
        }

        return $result;
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
