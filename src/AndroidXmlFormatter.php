<?php

declare(strict_types = 1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator;

use EugeneErg\IcuI18nTranslator\DataTransferObjects\FilePathContainer;
use EugeneErg\IcuI18nTranslator\FormatterInterface;
use SimpleXMLElement;

final readonly class AndroidXmlFormatter implements FormatterInterface
{

    public function format(FilePathContainer $file): string
    {
        // TODO: Implement format() method.
    }

    public function parse(string $content): FilePathContainer
    {
        $xml = new SimpleXMLElement($content);

        $children = [];

        foreach ($xml->children() as $node) {
            if ($node->getName() === 'string') {
                $children[(string)$node['name']] = (string)$node;
                continue;
            }

            if ($node->getName() === 'plurals') {
                $name = (string)$node['name'];

                $pluralMap = [];
                foreach ($node->item as $item) {
                    $quantity = (string)$item['quantity'];
                    $pluralMap[$quantity] = (string)$item;
                }

                // превращаем в ICU
                $icu = $this->buildIcuPlural($pluralMap);

                $children[$name] = $icu;
            }
        }

        return new FilePathContainer($children);
    }

    private function buildIcuPlural(array $pluralMap): string
    {
        $parts = [];

        foreach ($pluralMap as $quantity => $text) {
            $parts[] = "{$quantity} {{$text}}";
        }

        return '{count, plural, ' . implode(' ', $parts) . '}';
    }
}