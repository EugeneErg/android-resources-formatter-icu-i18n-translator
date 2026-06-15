<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlString;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlStringArray;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsBuilder;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsParser;
use EugeneErg\IcuI18nTranslator\DataTransferObjects\FilePathContainer;
use EugeneErg\IcuI18nTranslator\FormatterInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\Parser;
use RuntimeException;

use function is_string;

use const SORT_NUMERIC;

final readonly class AndroidIcuXmlFormatter implements FormatterInterface
{
    public function __construct(
        private AndroidStringsBuilder $androidStringsBuilder,
        private AndroidStringsParser $androidStringsParser,
        private Parser $parser,
    ) {
    }

    public function format(FilePathContainer $file): string
    {
        $result = [];

        foreach ($file->children as $name => $child) {
            if ($child instanceof FilePathContainer) {
                $result[$name] = $this->makeArray($child, (string) $name);
            } else {
                $result[$name] = new XmlString((string) $child);
            }
        }

        return $this->androidStringsBuilder->build($result);
    }

    public function parse(string $content): FilePathContainer
    {
        $items = $this->androidStringsParser->parse($content);
        $result = [];

        foreach ($items as $name => $item) {
            if ($item instanceof XmlString) {
                $result[$name] = $item->value;
            } elseif ($item instanceof XmlStringArray) {
                $children = [];

                foreach ($item->items as $key => $child) {
                    $children[$key] = $child;
                }

                $result[$name] = new FilePathContainer($children);
            } else {
                $options = [];

                foreach ($item->items as $key => $child) {
                    $options[$key] = $this->parser->parse($this->parser->quote($child))->types;
                }

                $result[$name] = new Types([Plural::create($name, $options)]);
            }
        }

        return new FilePathContainer($result);
    }

    private function makeArray(FilePathContainer $path, string $name): XmlStringArray
    {
        $result = [];
        $children = $path->children;
        ksort($children, SORT_NUMERIC);

        if (!array_is_list($children)) {
            throw new RuntimeException('Invalid string-array: ' . $name);
        }

        foreach ($path->children as $child) {
            if (!is_string($child)) {
                throw new RuntimeException('String-array items must be strings in ICU formatter');
            }

            $result[] = $child;
        }

        return new XmlStringArray($result);
    }
}
