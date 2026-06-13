<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlPlurals;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlString;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlStringArray;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsBuilder;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsParser;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects\FormatFlag;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects\FormatType;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects\PrintFormat;
use EugeneErg\IcuI18nTranslator\DataTransferObjects\FilePathContainer;
use EugeneErg\IcuI18nTranslator\FormatterInterface;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Date;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Duration;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Ordinal;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Pattern;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Plural;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\SpellOut;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Text;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Time;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Types;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Variable;
use EugeneErg\ICUMessageFormatParser\Parser;
use RuntimeException;

use function count;

use const PREG_SET_ORDER;
use const PREG_UNMATCHED_AS_NULL;
use const SORT_NUMERIC;

final readonly class AndroidXmlFormatter implements FormatterInterface
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
            $result[$name] = $child instanceof FilePathContainer
                ? $this->makeArray($child, (string) $name)
                : $this->icuToPrintF($child, true);
        }

        return $this->androidStringsBuilder->build($result);
    }

    public function parse(string $content): FilePathContainer
    {
        $items = $this->androidStringsParser->parse($content);
        $result = [];

        foreach ($items as $name => $item) {
            if ($item instanceof XmlString) {
                $result[$name] = $this->printFtoIcu($item->value);
            } elseif ($item instanceof XmlStringArray) {
                $children = [];

                foreach ($item->items as $key => $child) {
                    $children[$key] = $this->printFtoIcu($child);
                }

                $result[$name] = new FilePathContainer($children);
            } else {
                $options = [];

                foreach ($item->items as $key => $child) {
                    $options[$key] = $this->parser->parse($this->printFtoIcu($child))->types;
                }

                $result[$name] = new Types([Plural::create($name, $options)]);
            }
        }

        return new FilePathContainer($result);
    }

    private function parseString(string $input): array
    {
        $pattern = <<<'EOD'
            {
                        (?<spec>
                            %
                            (?:(?<index>\d+)\$)?
                            (?<flags>[
            EOD . implode('', array_column(FormatFlag::cases(), 'value')) . <<<'EOD'
            ]*)
                            (?<width>\d+)?
                            (?:\.(?<precision>\d+))?
                            (?<type>[
            EOD . implode('', array_column(FormatType::cases(), 'value')) . <<<'EOD'
            ])
                        )
                        |
                        (?<text>[^%]+)
                    }xu
            EOD;
        preg_match_all($pattern, $input, $matches, PREG_SET_ORDER|PREG_UNMATCHED_AS_NULL);

        $result = [];
        $indexes = [];

        foreach ($matches as $match) {
            if ($match['text'] !== null) {
                $result[] = $match['text'];

                continue;
            }

            $index = $match['index'] === null ? null : (int) $match['index'];

            if ($index === null) {
                $indexes[] = true;
                $index = array_key_last($indexes);
            } else {
                $indexes[$index] = true;
            }

            $flags = array_map([FormatFlag::class, 'from'], str_split($match['flags']));
            $width = $match['width'] === null ? null : (int) $match['width'];
            $precision = $match['precision'] === null ? null : (int) $match['precision'];
            $type = FormatType::from($match['type']);
            $result[] = new PrintFormat(type: $type, index: $index, width: $width, precision: $precision, flags: $flags);
        }

        return $result;
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
            $result[] = $this->icuToPrintF($child, false);
        }

        return new XmlStringArray($result);
    }

    private function icuToPrintF(Types|string $value, bool $tryPlural): XmlString|XmlPlurals
    {
        if (!$value instanceof Types) {
            $value = $this->parser->parse($value);
        }

        if (
            $tryPlural
            && count($value->types) === 1
            && $value->types[0] instanceof Plural
            && $value->types[0]->numbers === []
        ) {
            $icuPlural = $value->types[0];
            $items = [];
            $namedOptions = [
                'zero' => $icuPlural->zero,
                'one' => $icuPlural->one,
                'two' => $icuPlural->two,
                'few' => $icuPlural->few,
                'many' => $icuPlural->many,
            ];

            foreach ($namedOptions as $key => $value) {
                if ($value !== null) {
                    $items[$key] = $this->icuToPrintF($value, false)->value;
                }
            }

            return new XmlPlurals($items);
        }

        $result = [];

        foreach ($value->types as $item) {
            $result[] = match (true) {
                $item instanceof Date => $this->icuDateToPrintF($item),
                $item instanceof Duration => $this->icuDurationToPrintF($item),
                $item instanceof Number => $this->icuNumberToPrintF($item),
                $item instanceof Ordinal => $this->icuOrdinalToPrintF($item),
                $item instanceof Pattern => $this->icuPatternToPrintF($item),
                $item instanceof SpellOut => $this->icuSpellOutToPrintF($item),
                $item instanceof Text => $this->icuTextToPrintF($item),
                $item instanceof Time => $this->icuTimeToPrintF($item),
                $item instanceof Variable => $this->icuVariableToPrintF($item),
                default => throw new RuntimeException('Unexpected icu type: ' . $item::getName()),
            };
        }

        return new XmlString(implode('', $result));
    }

    private function printFtoIcu(string $value): string
    {
        $items = $this->parseString($value);
        $result = [];

        foreach ($items as $item) {
            if ($item instanceof PrintFormat) {
                $itemValue = $item->toIcuPattern();

                if ($itemValue === null) {
                    throw new RuntimeException('Unexpected print format: ' . $itemValue);
                }

                $result[] = $itemValue;
            } else {
                $result[] = $this->parser->quote($item);
            }
        }

        return implode('', $result);
    }

    private function icuDateToPrintF(Date $item): string
    {
        throw new RuntimeException('Not implemented');
    }

    private function icuDurationToPrintF(Duration $item): string
    {
        throw new RuntimeException('Not implemented');
    }

    private function icuNumberToPrintF(Number $item): string
    {
    }

    private function icuOrdinalToPrintF(Ordinal $item): string
    {
    }

    private function icuPatternToPrintF(Pattern $item): string
    {
    }

    private function icuSpellOutToPrintF(SpellOut $item): string
    {
    }

    private function icuTextToPrintF(Text $item): string
    {
        return $this->quote((string) $item);
    }

    private function icuTimeToPrintF(Time $item): string
    {
        throw new RuntimeException('Not implemented');
    }

    private function icuVariableToPrintF(Variable $item): string
    {
        if ((string) (int) $item->value !== $item->value) {
            throw new RuntimeException('Not implemented sting value');
        }

        return (string) new PrintFormat(FormatType::String, (int) $item->value);
    }

    private function quote(string $value): string
    {
        return str_replace(['%', "\n"], ['%%', '%n'], $value);
    }
}
