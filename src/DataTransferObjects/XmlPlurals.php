<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects;

final readonly class XmlPlurals
{
    /**
     * @param array<string, string> $items
     */
    public function __construct(public array $items)
    {
    }
}
