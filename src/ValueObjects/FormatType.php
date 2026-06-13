<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects;

enum FormatType: string
{
    case String = 's'; // строка
    case StringUpper = 'S'; // строка в верхнем регистре
    case Decimal = 'd'; // целое число (десятичное)
    case Float = 'f'; // число с плавающей точкой
    case Scientific = 'e'; // научная нотация (нижний регистр)
    case ScientificUpper = 'E'; // научная нотация (верхний регистр)
    case Char = 'c'; // символ (code point)
    case Boolean = 'b'; // boolean
    case Hex = 'x'; // шестнадцатеричное (нижний регистр)
    case HexUpper = 'X'; // шестнадцатеричное (верхний регистр)
    case Octal = 'o'; // восьмеричное
    case Newline = 'n'; // перенос строки
    case Percent = '%'; // литеральный знак %
}
