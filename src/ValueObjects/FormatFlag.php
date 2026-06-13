<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects;

enum FormatFlag: string
{
    case LeftAlign = '-'; // выравнивание влево
    case ForceSign = '+'; // всегда показывать знак
    case SpaceSign = ' '; // пробел перед положительным числом
    case ZeroPad = '0'; // заполнять нулями
    case GroupThousands = ','; // разделитель тысяч
    case Parentheses = '('; // отрицательные числа в скобках
}
