<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Tests\Parser;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlPlurals;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlString;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects\XmlStringArray;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsBuilder;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AndroidStringsParserBuilderTest extends TestCase
{
    private const XML = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <resources>
            <string name="hello">Hello</string>
            <string-array name="days">
                <item>Monday</item>
                <item>Tuesday</item>
            </string-array>
            <plurals name="items_count">
                <item quantity="one">%1$d item</item>
                <item quantity="other">%1$d items</item>
            </plurals>
        </resources>
        XML;

    #[Test]
    public function parseExtractsStringStringArrayAndPlurals(): void
    {
        $parser = new AndroidStringsParser();

        $result = $parser->parse(self::XML);

        $this->assertArrayHasKey('hello', $result);
        $this->assertInstanceOf(XmlString::class, $result['hello']);
        $this->assertSame('Hello', $result['hello']->value);

        $this->assertArrayHasKey('days', $result);
        $this->assertInstanceOf(XmlStringArray::class, $result['days']);
        $this->assertSame(['Monday', 'Tuesday'], $result['days']->items);

        $this->assertArrayHasKey('items_count', $result);
        $this->assertInstanceOf(XmlPlurals::class, $result['items_count']);
        $this->assertSame([
            'one' => '%1$d item',
            'other' => '%1$d items',
        ], $result['items_count']->items);
    }

    #[Test]
    public function buildProducesParsableXml(): void
    {
        $parser = new AndroidStringsParser();
        $builder = new AndroidStringsBuilder();

        $data = $parser->parse(self::XML);
        $xml = $builder->build($data);
        $rebuilt = $parser->parse($xml);

        $this->assertEquals($data, $rebuilt);
    }

    #[Test]
    public function buildEscapesSpecialCharacters(): void
    {
        $builder = new AndroidStringsBuilder();

        $xml = $builder->build([
            'with_special' => new XmlString('5 < 10 & "quoted" \'apostrophe\''),
        ]);

        $parser = new AndroidStringsParser();
        $rebuilt = $parser->parse($xml);

        $entry = $rebuilt['with_special'];
        $this->assertInstanceOf(XmlString::class, $entry);
        $this->assertSame('5 < 10 & "quoted" \'apostrophe\'', $entry->value);
    }
}
