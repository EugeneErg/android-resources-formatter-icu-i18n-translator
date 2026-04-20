<?php

declare(strict_types = 1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlString;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsBuilder;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsParser;
use EugeneErg\IcuI18nTranslator\DataTransferObjects\FilePathContainer;
use EugeneErg\IcuI18nTranslator\FormatterInterface;
use SimpleXMLElement;

final readonly class AndroidIcuXmlFormatter implements FormatterInterface
{
    public function __construct(
        private AndroidStringsBuilder $androidStringsBuilder,
        private AndroidStringsParser $androidStringsParser,
    ) {
    }

    public function format(FilePathContainer $file): string
    {
        $result = [];

        foreach ($file->children as $name => $child) {
            if ($child instanceof FilePathContainer) {
                $result[$name] = $this->makeArray($child);
            } else {
                $result[$name] = new XmlString((string) $child);
            }
        }

        return $this->androidStringsBuilder->build($result);
    }

    public function parse(string $content): FilePathContainer
    {
        $items = $this->androidStringsParser->parse($content);

        $xml = new SimpleXMLElement($content);

        $children = [];

        foreach ($xml->children() as $node) {
            if ($node->getName() !== 'messages') {
                continue;
            }

            $children[(string) $node['name']] = (string) $node;
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