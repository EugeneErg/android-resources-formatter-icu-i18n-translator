<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Tests\ValueObjects;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects\FormatFlag;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects\FormatType;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects\PrintFormat;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class PrintFormatTest extends TestCase
{
    #[Test]
    #[DataProvider('provideFromStringAndBackToStringCases')]
    public function fromStringAndBackToString(string $specifier): void
    {
        $format = PrintFormat::fromString($specifier);

        $this->assertSame($specifier, (string) $format);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function provideFromStringAndBackToStringCases(): iterable
    {
        return [
            'plain string' => ['%s'],
            'indexed string' => ['%1$s'],
            'decimal' => ['%1$d'],
            'float with precision' => ['%1$.2f'],
            'scientific' => ['%1$e'],
            'zero padded decimal' => ['%05d'],
            'force sign decimal' => ['%+d'],
            'parentheses decimal' => ['%(d'],
            'group thousands' => ['%,d'],
            'left aligned width' => ['%-10s'],
        ];
    }

    #[Test]
    public function fromStringRejectsInvalidSpecifier(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PrintFormat::fromString('not a specifier');
    }

    #[Test]
    public function fromStringRejectsUnknownType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        PrintFormat::fromString('%1$z');
    }

    #[Test]
    public function hasFlag(): void
    {
        $format = PrintFormat::fromString('%+05d');

        $this->assertTrue($format->hasFlag(FormatFlag::ForceSign));
        $this->assertTrue($format->hasFlag(FormatFlag::ZeroPad));
        $this->assertFalse($format->hasFlag(FormatFlag::GroupThousands));
    }

    #[Test]
    public function isNumericAndIsString(): void
    {
        $decimal = new PrintFormat(FormatType::Decimal, 1);
        $string = new PrintFormat(FormatType::String, 1);

        $this->assertTrue($decimal->isNumeric());
        $this->assertFalse($decimal->isString());
        $this->assertTrue($string->isString());
        $this->assertFalse($string->isNumeric());
    }

    #[Test]
    public function argNameAndArgIndexRoundTrip(): void
    {
        $this->assertSame('0', PrintFormat::argName(0));
        $this->assertSame('12', PrintFormat::argName(12));

        $this->assertSame(0, PrintFormat::argIndex('0'));
        $this->assertSame(12, PrintFormat::argIndex('12'));
        $this->assertNull(PrintFormat::argIndex('count'));
        $this->assertNull(PrintFormat::argIndex(''));
        $this->assertNull(PrintFormat::argIndex('01a'));
    }

    #[Test]
    public function describeIncludesAllParts(): void
    {
        $format = new PrintFormat(FormatType::Float, 1, width: 10, precision: 2, flags: [FormatFlag::ZeroPad]);

        $description = $format->describe();

        $this->assertStringContainsString('аргумент #1', $description);
        $this->assertStringContainsString('заполнение нулями', $description);
        $this->assertStringContainsString('ширина 10', $description);
        $this->assertStringContainsString('точность 2', $description);
        $this->assertStringContainsString('число с плавающей точкой', $description);
    }

    #[Test]
    public function toIcuPatternForString(): void
    {
        $format = new PrintFormat(FormatType::String, 1);

        $this->assertSame('{0}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForDecimal(): void
    {
        $format = new PrintFormat(FormatType::Decimal, 1);

        $this->assertSame('{0, number, ::integer}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForFloatWithPrecision(): void
    {
        $format = new PrintFormat(FormatType::Float, 1, precision: 2);

        $this->assertSame('{0, number, #.##}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForPlainFloat(): void
    {
        $format = new PrintFormat(FormatType::Float, 1);

        $this->assertSame('{0, number, ::}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForScientific(): void
    {
        $format = new PrintFormat(FormatType::Scientific, 1);

        $this->assertSame('{0, number, ::scientific}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForZeroPaddedDecimal(): void
    {
        $format = new PrintFormat(FormatType::Decimal, 1, width: 5, flags: [FormatFlag::ZeroPad]);

        $this->assertSame('{0, number, 00000}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForZeroPaddedFloat(): void
    {
        $format = new PrintFormat(FormatType::Float, 1, width: 10, precision: 2, flags: [FormatFlag::ZeroPad]);

        $this->assertSame('{0, number, 0000000.##}', $format->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternForPercentAndNewline(): void
    {
        $this->assertSame('%', (new PrintFormat(FormatType::Percent))->toIcuPattern());
        $this->assertSame("\n", (new PrintFormat(FormatType::Newline))->toIcuPattern());
    }

    #[Test]
    public function toIcuPatternUsesPositionWhenIndexIsNull(): void
    {
        $format = new PrintFormat(FormatType::String);

        $this->assertSame('{2}', $format->toIcuPattern(2));
    }
}
