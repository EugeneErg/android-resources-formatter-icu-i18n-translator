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
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Message;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Currency;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\DecimalSeparator;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Format;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Grouping;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\MeasureUnit;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Notation;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Precision;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\PrecisionFraction;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Sign;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\Skeleton;
use EugeneErg\ICUMessageFormatParser\DataTransferObjects\Number\UnitWidth;
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
use function is_string;
use function strlen;

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
            if ($child instanceof FilePathContainer) {
                $result[$name] = $this->makeArray($child, (string) $name);
            } else {
                $result[$name] = $this->icuToPrintF($child, true);
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

    /**
     * @return array<int, PrintFormat|string>
     */
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

        $consumed = implode('', array_column($matches, 0));

        if ($consumed !== $input) {
            throw new RuntimeException('Unsupported format specifier in: ' . $input);
        }

        $result = [];

        foreach ($matches as $match) {
            if ($match['text'] !== null) {
                $result[] = (string) $match['text'];

                continue;
            }

            $index = $match['index'] === null ? null : (int) $match['index'];
            $flagStr = (string) ($match['flags'] ?? '');
            $flags = $flagStr !== '' ? array_map([FormatFlag::class, 'from'], str_split($flagStr)) : [];
            $width = $match['width'] === null ? null : (int) $match['width'];
            $precision = $match['precision'] === null ? null : (int) $match['precision'];
            $type = FormatType::from((string) $match['type']);
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
            if (!$child instanceof Types && !is_string($child)) {
                throw new RuntimeException('Nested FilePathContainer not supported inside string-array: ' . $name);
            }

            $result[] = $this->icuTypesToPrintF($this->toTypes($child));
        }

        return new XmlStringArray($result);
    }

    private function toTypes(Types|string $value): Types
    {
        return $value instanceof Types ? $value : $this->parser->parse($value);
    }

    private function icuToPrintF(Types|string $value, bool $tryPlural): XmlString|XmlPlurals
    {
        $value = $this->toTypes($value);

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

            foreach ($namedOptions as $key => $option) {
                if ($option !== null) {
                    $items[$key] = $this->icuTypesToPrintF($option);
                }
            }

            $items['other'] = $this->icuTypesToPrintF($icuPlural->other);

            return new XmlPlurals($items);
        }

        return new XmlString($this->icuTypesToPrintF($value));
    }

    private function icuTypesToPrintF(Types $value): string
    {
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
                default => throw new RuntimeException('Unexpected icu type: ' . $item::class),
            };
        }

        return implode('', $result);
    }

    private function printFtoIcu(string $value): string
    {
        $items = $this->parseString($value);
        $result = [];
        $position = 0;

        foreach ($items as $item) {
            if ($item instanceof PrintFormat) {
                $itemValue = $item->toIcuPattern($position);

                if ($item->index === null) {
                    ++$position;
                }

                if ($itemValue === null) {
                    throw new RuntimeException('Unexpected print format: ' . $itemValue);
                }

                $result[] = $itemValue;
            } else {
                $result[] = $this->parser->quote((string) $item);
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

    /**
     * Преобразует ICU {N, number, ...} обратно в printf-спецификатор.
     *
     * Поддерживается только подмножество, достижимое из {@see PrintFormat::toIcuPattern()}
     * без флага группировки (','), который в ICU не имеет однозначного обратного
     * соответствия и поэтому игнорируется.
     */
    private function icuNumberToPrintF(Number $item): string
    {
        $icuIndex = PrintFormat::argIndex($item->value);

        if ($icuIndex === null) {
            throw new RuntimeException('Not implemented');
        }

        $index = $icuIndex + 1;

        if ($item->options instanceof Skeleton) {
            return (string) $this->numberSkeletonToPrintFormat($item->options, $index);
        }

        // $item->options is Message here
        if (
            count($item->options->values) === 1
            && $item->options->values[0] instanceof Pattern
        ) {
            return (string) $this->decimalPatternToPrintFormat($item->options->values[0]->value, $index);
        }

        throw new RuntimeException('Not implemented');
    }

    private function numberSkeletonToPrintFormat(Skeleton $skeleton, int $index): PrintFormat
    {
        if (
            $skeleton->format instanceof Currency
            || $skeleton->format instanceof MeasureUnit
            || $skeleton->format === Format::Percent
            || $skeleton->format === Format::Permille
            || $skeleton->format === Format::BaseUnit
            || $skeleton->unitWidth !== UnitWidth::Short
            || $skeleton->scale !== 1.0
            || $skeleton->roundingMode !== null
            || $skeleton->decimalSeparator !== DecimalSeparator::Auto
            || $skeleton->numberingSystem !== null
        ) {
            throw new RuntimeException('Not implemented');
        }

        $flags = match ($skeleton->sign) {
            Sign::Auto => [],
            Sign::Always => [FormatFlag::ForceSign],
            Sign::Accounting, Sign::AccountingAlways, Sign::AccountingExceptZero, Sign::AccountingNegative => [FormatFlag::Parentheses],
            Sign::Never, Sign::ExceptZero, Sign::Negative => throw new RuntimeException('Not implemented'),
        };

        $flags = match ($skeleton->grouping) {
            Grouping::Auto => $flags,
            Grouping::Thousands => [...$flags, FormatFlag::GroupThousands],
            Grouping::Off, Grouping::Min2, Grouping::OnAligned => throw new RuntimeException('Not implemented'),
        };

        // Научная нотация: только дефолтная точность (как генерирует toIcuPattern()).
        if ($skeleton->notation === Notation::Scientific) {
            if (
                $skeleton->format !== Format::Decimal
                || $skeleton->integerWidth !== null
                || !$skeleton->precision instanceof PrecisionFraction
                || $skeleton->precision->minFraction !== 6
                || $skeleton->precision->maxFraction !== 6
                || $skeleton->precision->trailingZeroHideIfWhole
                || $skeleton->precision->minSignificantDigits !== null
                || $skeleton->precision->maxSignificantDigits !== null
                || $skeleton->precision->significantDigitsMode !== null
            ) {
                throw new RuntimeException('Not implemented');
            }

            return new PrintFormat(FormatType::Scientific, $index, flags: $flags);
        }

        if ($skeleton->notation !== Notation::Standard) {
            throw new RuntimeException('Not implemented');
        }

        if ($skeleton->format !== Format::Decimal && $skeleton->format !== Format::Integer) {
            throw new RuntimeException('Not implemented');
        }

        $precision = $skeleton->precision;

        if ($precision === Precision::Integer) {
            if ($skeleton->integerWidth !== null) {
                if ($skeleton->integerWidth->truncateAt !== null) {
                    throw new RuntimeException('Not implemented');
                }

                return new PrintFormat(
                    FormatType::Decimal,
                    $index,
                    width: $skeleton->integerWidth->zeroFillTo,
                    flags: [...$flags, FormatFlag::ZeroPad],
                );
            }

            return new PrintFormat(FormatType::Decimal, $index, flags: $flags);
        }

        if (!$precision instanceof PrecisionFraction) {
            throw new RuntimeException('Not implemented');
        }

        if (
            $precision->trailingZeroHideIfWhole
            || $precision->minSignificantDigits !== null
            || $precision->significantDigitsMode !== null
            || $precision->maxFraction === null
        ) {
            throw new RuntimeException('Not implemented');
        }

        // "::"-дефолт для Decimal (min=0, max=2): соответствует %f без точности,
        // либо %0Nd, если задана ширина с заполнением нулями.
        if ($skeleton->format === Format::Decimal && $precision->minFraction === 0 && $precision->maxFraction === 2) {
            if ($skeleton->integerWidth !== null) {
                if ($skeleton->integerWidth->truncateAt !== null) {
                    throw new RuntimeException('Not implemented');
                }

                return new PrintFormat(
                    FormatType::Decimal,
                    $index,
                    width: $skeleton->integerWidth->zeroFillTo,
                    flags: [...$flags, FormatFlag::ZeroPad],
                );
            }

            return new PrintFormat(FormatType::Float, $index, flags: $flags);
        }

        if ($precision->minFraction === $precision->maxFraction) {
            $width = null;

            if ($skeleton->integerWidth !== null) {
                if ($skeleton->integerWidth->truncateAt !== null) {
                    throw new RuntimeException('Not implemented');
                }

                $width = $skeleton->integerWidth->zeroFillTo + ($precision->minFraction > 0 ? $precision->minFraction + 1 : 0);
                $flags[] = FormatFlag::ZeroPad;
            }

            return new PrintFormat(FormatType::Float, $index, width: $width, precision: $precision->minFraction, flags: $flags);
        }

        throw new RuntimeException('Not implemented');
    }

    /**
     * Разбирает DecimalFormat-паттерн (без skeleton, например "#.##" или "00000.##")
     * обратно в printf-спецификатор.
     */
    private function decimalPatternToPrintFormat(string $pattern, int $index): PrintFormat
    {
        $flags = [];

        if (str_contains($pattern, ';')) {
            [$positive, $negative] = explode(';', $pattern, 2);

            if ($negative === '(' . $positive . ')') {
                $flags[] = FormatFlag::Parentheses;
                $pattern = $positive;
            } elseif (
                str_starts_with($positive, '+')
                && str_starts_with($negative, '-')
                && substr($positive, 1) === substr($negative, 1)
            ) {
                $flags[] = FormatFlag::ForceSign;
                $pattern = substr($positive, 1);
            } else {
                throw new RuntimeException('Not implemented');
            }
        }

        if (preg_match('{^0+$}', $pattern) === 1) {
            return new PrintFormat(FormatType::Decimal, $index, width: strlen($pattern), flags: [...$flags, FormatFlag::ZeroPad]);
        }

        if ($pattern === '#,##0') {
            return new PrintFormat(FormatType::Decimal, $index, flags: [...$flags, FormatFlag::GroupThousands]);
        }

        if ($pattern === '#') {
            return new PrintFormat(FormatType::Float, $index, precision: 0, flags: $flags);
        }

        if (preg_match('{^#\\.(#+)$}', $pattern, $matches) === 1) {
            return new PrintFormat(FormatType::Float, $index, precision: strlen($matches[1]), flags: $flags);
        }

        if (preg_match('{^(0+)\\.(#+)$}', $pattern, $matches) === 1) {
            return new PrintFormat(
                FormatType::Float,
                $index,
                width: strlen($matches[1]) + strlen($matches[2]) + 1,
                precision: strlen($matches[2]),
                flags: [...$flags, FormatFlag::ZeroPad],
            );
        }

        throw new RuntimeException('Not implemented');
    }

    private function icuOrdinalToPrintF(Ordinal $item): string
    {
        throw new RuntimeException('Not implemented');
    }

    private function icuPatternToPrintF(Pattern $item): string
    {
        return $this->quote($item->value);
    }

    private function icuSpellOutToPrintF(SpellOut $item): string
    {
        throw new RuntimeException('Not implemented');
    }

    private function icuTextToPrintF(Text $item): string
    {
        return $this->quote($item->value);
    }

    private function icuTimeToPrintF(Time $item): string
    {
        throw new RuntimeException('Not implemented');
    }

    private function icuVariableToPrintF(Variable $item): string
    {
        $icuIndex = PrintFormat::argIndex($item->value);

        if ($icuIndex === null) {
            throw new RuntimeException('Not implemented');
        }

        return (string) new PrintFormat(FormatType::String, $icuIndex + 1);
    }

    private function quote(string $value): string
    {
        return str_replace(['%', "\n"], ['%%', '%n'], $value);
    }
}
