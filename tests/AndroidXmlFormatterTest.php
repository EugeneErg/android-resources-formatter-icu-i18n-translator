<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Tests;

use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\AndroidXmlFormatter;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsBuilder;
use EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\Parser\AndroidStringsParser;
use EugeneErg\ICUMessageFormatParser\Parser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use const ENT_XML1;

/**
 * @internal
 */
final class AndroidXmlFormatterTest extends TestCase
{
    /**
     * @phpstan-ignore property.uninitialized
     */
    private AndroidXmlFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new AndroidXmlFormatter(
            new AndroidStringsBuilder(),
            new AndroidStringsParser(),
            new Parser(),
        );
    }

    #[Test]
    #[DataProvider('provideRoundTripCases')]
    public function roundTrip(string $input, string $expectedOutput): void
    {
        $path = $this->formatter->parse($this->wrap($input));
        $xml = $this->formatter->format($path);

        $this->assertSame($expectedOutput, $this->extract($xml));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function provideRoundTripCases(): iterable
    {
        return [
            'plain text' => ['Hello, world!', 'Hello, world!'],
            'simple string' => ['Hello, %s!', 'Hello, %1$s!'],
            'indexed string' => ['Hello, %1$s!', 'Hello, %1$s!'],
            'decimal' => ['Count: %1$d', 'Count: %1$d'],
            'float with precision' => ['Price: %1$.2f', 'Price: %1$.2f'],
            'plain float' => ['Value: %1$f', 'Value: %1$f'],
            'scientific' => ['%1$e', '%1$e'],
            'two arguments' => ['%1$s has %2$d items', '%1$s has %2$d items'],
            'two implicit arguments' => ['%s and %s', '%1$s and %2$s'],
            'zero padded decimal' => ['%05d', '%1$05d'],
            'force sign decimal' => ['%+d', '%1$+d'],
            'parentheses decimal' => ['%(d', '%1$(d'],
            'group thousands' => ['%,d', '%1$,d'],
            'force sign zero padded float' => ['%+010.2f', '%1$+010.2f'],
            'force sign float with precision' => ['%+.4f', '%1$+.4f'],
            'literal percent' => ['100%%', '100%%'],
            'newline' => ['a%nb', 'a%nb'],
        ];
    }

    #[Test]
    public function stringArrayRoundTrip(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="utf-8"?>
            <resources>
                <string-array name="days">
                    <item>Monday</item>
                    <item>Tuesday</item>
                </string-array>
            </resources>
            XML;

        $path = $this->formatter->parse($xml);
        $output = $this->formatter->format($path);

        $this->assertStringContainsString('<item>Monday</item>', $output);
        $this->assertStringContainsString('<item>Tuesday</item>', $output);
    }

    #[Test]
    public function pluralsRoundTrip(): void
    {
        $xml = <<<'XML'
            <?xml version="1.0" encoding="utf-8"?>
            <resources>
                <plurals name="items_count">
                    <item quantity="one">%1$d item</item>
                    <item quantity="other">%1$d items</item>
                </plurals>
            </resources>
            XML;

        $path = $this->formatter->parse($xml);
        $output = $this->formatter->format($path);

        $this->assertStringContainsString('<item quantity="one">%1$d item</item>', $output);
        $this->assertStringContainsString('<item quantity="other">%1$d items</item>', $output);
    }

    #[Test]
    public function unsupportedJavaDateSpecifierThrows(): void
    {
        $this->expectException(RuntimeException::class);

        $this->formatter->parse($this->wrap('%1$tY-%1$tm-%1$td'));
    }

    private function wrap(string $value): string
    {
        return '<?xml version="1.0" encoding="utf-8"?><resources><string name="t">'
            . htmlspecialchars($value, ENT_XML1)
            . '</string></resources>';
    }

    private function extract(string $xml): string
    {
        preg_match('{<string name="t">(.*?)</string>}s', $xml, $matches);

        return $matches[1] ?? '';
    }
}
