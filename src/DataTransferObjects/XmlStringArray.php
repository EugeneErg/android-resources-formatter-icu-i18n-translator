<?php

declare(strict_types = 1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\DataTransferObjects;

final readonly class XmlStringArray
{
    /**
     * @param string[] $items
     */
    public function __construct(public array $items)
    {
    }
}