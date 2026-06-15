<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Tests;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\AndroidIcuXmlFormatter;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsBuilder;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsParser;
use EugeneErg\IcuI18nTranslator\DataTransferObjects\FilePathContainer;
use EugeneErg\ICUMessageFormatParser\Parser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class AndroidIcuXmlFormatterTest extends TestCase
{
    private const XML = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <resources>
            <string name="hello">Hello</string>
            <string name="welcome">Welcome, {name}!</string>
            <string name="items_count">{count, plural, one {# item} other {# items}}</string>
            <string-array name="days">
                <item>Monday</item>
                <item>Tuesday</item>
            </string-array>
        </resources>
        XML;

    /**
     * @phpstan-ignore property.uninitialized
     */
    private AndroidIcuXmlFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new AndroidIcuXmlFormatter(
            new AndroidStringsBuilder(),
            new AndroidStringsParser(),
            new Parser(),
        );
    }

    #[Test]
    public function parseExtractsPlainStringAsString(): void
    {
        $path = $this->formatter->parse(self::XML);

        $this->assertSame('Hello', $path->children['hello']);
    }

    #[Test]
    public function parseKeepsIcuVariableSyntaxAsIs(): void
    {
        $path = $this->formatter->parse(self::XML);

        $this->assertSame('Welcome, {name}!', $path->children['welcome']);
    }

    #[Test]
    public function parseTurnsInlinePluralIntoTypes(): void
    {
        $path = $this->formatter->parse(self::XML);

        $child = $path->children['items_count'];
        $this->assertIsString($child);
        $this->assertSame('{count, plural, one {# item} other {# items}}', $child);
    }

    #[Test]
    public function parseStringArray(): void
    {
        $path = $this->formatter->parse(self::XML);

        $days = $path->children['days'];
        $this->assertInstanceOf(FilePathContainer::class, $days);
        $this->assertSame(['Monday', 'Tuesday'], $days->children);
    }

    #[Test]
    public function formatRoundTrip(): void
    {
        $path = $this->formatter->parse(self::XML);
        $output = $this->formatter->format($path);
        $rebuilt = $this->formatter->parse($output);

        $this->assertEquals($path, $rebuilt);
    }

    #[Test]
    public function formatPassesThroughStringArrayItemsAsIs(): void
    {
        $path = new FilePathContainer([
            'colors' => new FilePathContainer(["red's {favorite}"]),
        ]);

        $output = $this->formatter->format($path);

        $this->assertStringContainsString("red's {favorite}", $output);

        $reparsed = $this->formatter->parse($output);
        $colors = $reparsed->children['colors'];
        $this->assertInstanceOf(FilePathContainer::class, $colors);
        $this->assertSame("red's {favorite}", $colors->children[0]);

        $output2 = $this->formatter->format($reparsed);

        $this->assertSame($output, $output2);
    }
}
