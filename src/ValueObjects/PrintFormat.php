<?php

declare(strict_types=1);

namespace EugeneErg\AndroidResourcesFormatterIcuI18nTranslator\ValueObjects;

use InvalidArgumentException;
use Stringable;

use function in_array;
use function strlen;

final readonly class PrintFormat implements Stringable
{
    /**
     * Префикс для имён ICU-переменных, соответствующих позиционным printf-аргументам.
     *
     * Начиная с eugene-erg/icu-message-format-parser 1.2.7 числовые имена переменных
     * (как в "{0}") поддерживаются, поэтому префикс пустой и индекс используется как есть.
     */
    private const ARG_PREFIX = '';

    /**
     * @param FormatFlag[] $flags
     */
    public function __construct(
        public FormatType $type,
        public int|null $index = null,
        public int|null $width = null,
        public int|null $precision = null,
        public array $flags = [],
    ) {
    }

    public function __toString(): string
    {
        $result = '%';

        if ($this->index !== null) {
            $result .= $this->index . '$';
        }

        foreach ($this->flags as $flag) {
            $result .= $flag->value;
        }

        if ($this->width !== null) {
            $result .= $this->width;
        }

        if ($this->precision !== null) {
            $result .= '.' . $this->precision;
        }

        $result .= $this->type->value;

        return $result;
    }

    /**
     * Парсит одиночный спецификатор формата, например "%1$-10.2f".
     * Строка должна начинаться с '%'.
     *
     * @throws InvalidArgumentException если строка не является валидным спецификатором
     */
    public static function fromString(string $specifier): self
    {
        $pattern = '/^%(?:(\\d+)\\$)?([-+ 0,(]*)(\\d+)?(?:\\.(\\d+))?([sS dDefEcbxXon%])$/';

        if (!preg_match($pattern, $specifier, $matches)) {
            throw new InvalidArgumentException("Невалидный printf-спецификатор: \"{$specifier}\"");
        }

        [, $rawIndex, $rawFlags, $rawWidth, $rawPrecision, $rawType] = $matches;
        $type = FormatType::tryFrom($rawType);

        if ($type === null) {
            throw new InvalidArgumentException("Неизвестный тип форматирования: \"{$rawType}\"");
        }

        $index = $rawIndex !== '' ? (int) $rawIndex : null;
        $flags = [];

        foreach (str_split($rawFlags) as $flagChar) {
            $flag = FormatFlag::tryFrom($flagChar);

            if ($flag !== null) {
                $flags[] = $flag;
            }
        }

        $width = $rawWidth !== '' ? (int) $rawWidth : null;
        $precision = $rawPrecision !== '' ? (int) $rawPrecision : null;

        return new self(type: $type, index: $index, width: $width, precision: $precision, flags: $flags);
    }

    /**
     * Преобразует 0-based индекс аргумента в имя ICU-переменной (например, "arg0").
     */
    public static function argName(int $icuIndex): string
    {
        return self::ARG_PREFIX . $icuIndex;
    }

    /**
     * Извлекает 0-based индекс аргумента из имени ICU-переменной (например, "arg0" → 0).
     * Возвращает null, если имя не соответствует формату "arg<N>".
     */
    public static function argIndex(string $name): int|null
    {
        if (preg_match('/^' . self::ARG_PREFIX . '(\\d+)$/', $name, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    public function hasFlag(FormatFlag $flag): bool
    {
        return in_array($flag, $this->flags, strict: true);
    }

    public function isNumeric(): bool
    {
        return in_array($this->type, [
            FormatType::Decimal,
            FormatType::Float,
            FormatType::Scientific,
            FormatType::ScientificUpper,
            FormatType::Hex,
            FormatType::HexUpper,
            FormatType::Octal,
        ], strict: true);
    }

    public function isString(): bool
    {
        return in_array($this->type, [
            FormatType::String,
            FormatType::StringUpper,
        ], strict: true);
    }

    public function describe(): string
    {
        $parts = [];

        if ($this->index !== null) {
            $parts[] = "аргумент #{$this->index}";
        }

        foreach ($this->flags as $flag) {
            $parts[] = match ($flag) {
                FormatFlag::LeftAlign => 'выравнивание влево',
                FormatFlag::ForceSign => 'знак всегда',
                FormatFlag::SpaceSign => 'пробел перед числом',
                FormatFlag::ZeroPad => 'заполнение нулями',
                FormatFlag::GroupThousands => 'разделитель тысяч',
                FormatFlag::Parentheses => 'скобки для отрицательных',
            };
        }

        if ($this->width !== null) {
            $parts[] = "ширина {$this->width}";
        }

        if ($this->precision !== null) {
            $parts[] = "точность {$this->precision}";
        }

        $parts[] = match ($this->type) {
            FormatType::String => 'строка',
            FormatType::StringUpper => 'строка (UPPER)',
            FormatType::Decimal => 'целое число',
            FormatType::Float => 'число с плавающей точкой',
            FormatType::Scientific => 'научная нотация',
            FormatType::ScientificUpper => 'научная нотация (верхний регистр)',
            FormatType::Char => 'символ',
            FormatType::Boolean => 'boolean',
            FormatType::Hex => 'hex (нижний регистр)',
            FormatType::HexUpper => 'hex (верхний регистр)',
            FormatType::Octal => 'восьмеричное',
            FormatType::Newline => 'перенос строки',
            FormatType::Percent => 'знак процента',
        };

        return implode(', ', $parts);
    }

    // ─────────────────────────────────────────
    // ICU conversion
    // ─────────────────────────────────────────

    /**
     * Конвертирует спецификатор в ICU паттерн.
     * Если возможно несколько вариантов — выбирается самый короткий.
     * Возвращает null если конвертация невозможна.
     *
     * @param int $position Порядковый номер аргумента (0-based), используется
     *                      когда index не задан (%s без %1$). Например, первый
     *                      %s в строке → position=0.
     */
    public function toIcuPattern(int $position = 0): string|null
    {
        $icuIndex = $this->index !== null ? $this->index - 1 : $position;
        $argName = self::argName($icuIndex);

        return match ($this->type) {
            FormatType::Percent => '%',
            FormatType::Newline => "\n",
            FormatType::String => "{{$argName}}",
            FormatType::Decimal => $this->shortest(
                $this->buildIcuIntegerSkeleton($icuIndex),
                $this->buildIcuIntegerPattern($icuIndex),
            ),
            FormatType::Float => $this->shortest(
                $this->buildIcuFloatSkeleton($icuIndex),
                $this->buildIcuFloatPattern($icuIndex),
            ),
            FormatType::Scientific => "{{$argName}, number, ::scientific}",
            FormatType::Boolean => "{{$argName}, select, true{true} false{false} other{false}}",
            default => null,
        };
    }

    /**
     * Выбирает самый короткий вариант из непустых.
     * Если все варианты null — возвращает null.
     */
    private function shortest(string|null ...$candidates): string|null
    {
        $valid = array_filter($candidates, static fn ($c) => $c !== null);

        if (empty($valid)) {
            return null;
        }

        return array_reduce(
            $valid,
            static fn (string|null $carry, string $item) => $carry === null || strlen($item) < strlen($carry) ? $item : $carry,
            null,
        );
    }

    /**
     * Вариант через skeleton (::).
     * Например: %05d → {0, number, ::00000}
     *           %+d  → {0, number, ::+! integer}.
     */
    private function buildIcuIntegerSkeleton(int $icuIndex): string
    {
        $tokens = [];

        // Знак
        if ($this->hasFlag(FormatFlag::ForceSign)) {
            $tokens[] = 'sign-always';
        } elseif ($this->hasFlag(FormatFlag::Parentheses)) {
            $tokens[] = 'sign-accounting';
        }

        // Разделитель тысяч
        if ($this->hasFlag(FormatFlag::GroupThousands)) {
            $tokens[] = 'group-thousands';
        }

        // Ведущие нули: %05d → 00000
        if ($this->hasFlag(FormatFlag::ZeroPad) && $this->width !== null) {
            $tokens[] = str_repeat('0', $this->width);
        } else {
            $tokens[] = 'integer';
        }

        return '{' . self::argName($icuIndex) . ', number, ::' . implode(' ', $tokens) . '}';
    }

    /**
     * Вариант через DecimalFormat паттерн (без ::).
     * Например: %05d → {0, number, 00000}
     *           %,d  → {0, number, #,##0}.
     */
    private function buildIcuIntegerPattern(int $icuIndex): string|null
    {
        // Паттерн не поддерживает знак через префикс в том же виде,
        // поэтому для sign-always / sign-accounting возвращаем null —
        // skeleton всегда лучше
        if ($this->hasFlag(FormatFlag::ForceSign) || $this->hasFlag(FormatFlag::Parentheses)) {
            return null;
        }

        if ($this->hasFlag(FormatFlag::ZeroPad) && $this->width !== null) {
            $pattern = str_repeat('0', $this->width);

            if ($this->hasFlag(FormatFlag::GroupThousands)) {
                // Вставляем группировку: 00000 → #,##0 с min-digits
                $pattern = '#,##' . str_repeat('0', max(1, $this->width - 4));
            }

            return '{' . self::argName($icuIndex) . ', number, ' . $pattern . '}';
        }

        if ($this->hasFlag(FormatFlag::GroupThousands)) {
            return '{' . self::argName($icuIndex) . ', number, #,##0}';
        }

        // Базовый integer — skeleton короче
        return null;
    }

    // ── Дробные числа ───────────────────────────────────────────────────────

    /**
     * Вариант через skeleton (::).
     * Например: %.2f  → {0, number, ::.##}
     *           %+.2f → {0, number, ::+! .##}.
     */
    private function buildIcuFloatSkeleton(int $icuIndex): string|null
    {
        $tokens = [];

        $hasZeroPadWidth = $this->hasFlag(FormatFlag::ZeroPad) && $this->width !== null;

        // ForceSign + ZeroPad не выражается в skeleton (паттерн-форма справляется)
        if ($hasZeroPadWidth && ($this->hasFlag(FormatFlag::ForceSign) || $this->hasFlag(FormatFlag::Parentheses))) {
            return null;
        }

        // Знак
        if ($this->hasFlag(FormatFlag::ForceSign)) {
            $tokens[] = 'sign-always';
        } elseif ($this->hasFlag(FormatFlag::Parentheses)) {
            $tokens[] = 'sign-accounting';
        }

        // Разделитель тысяч
        if ($this->hasFlag(FormatFlag::GroupThousands)) {
            $tokens[] = 'group-thousands';
        }

        // Ведущие нули + ширина: %010.2f
        // Ширина = все символы включая знак, цифры, точку, дробь
        if ($hasZeroPadWidth) {
            $fracPrecision = $this->precision ?? 6; // printf default
            $fracLen = $fracPrecision > 0 ? $fracPrecision + 1 : 0; // +1 для точки
            $intDigits = max(1, $this->width - $fracLen);
            $fracPart = $fracPrecision > 0
                ? '.' . str_repeat('#', $fracPrecision)
                : '';
            $tokens[] = str_repeat('0', $intDigits) . $fracPart;
        } elseif ($this->precision !== null) {
            // Только точность
            $tokens[] = $this->precision === 0
                ? 'precision-integer'
                : '.' . str_repeat('#', $this->precision);
        }
        // Без точности и без zeroPad — пустой skeleton (базовый float)

        return '{' . self::argName($icuIndex) . ', number, ::' . implode(' ', $tokens) . '}';
    }

    /**
     * Вариант через DecimalFormat паттерн (без ::).
     * Например: %.2f   → {0, number, #.##}
     *           %(,f   → {0, number, #,##0.######;(#,##0.######)}
     *           %010.2f → {0, number, 00000000.##}.
     */
    private function buildIcuFloatPattern(int $icuIndex): string
    {
        // Parentheses реализуется через ; в DecimalFormat паттерне
        $hasParens = $this->hasFlag(FormatFlag::Parentheses);
        $hasGroup = $this->hasFlag(FormatFlag::GroupThousands);

        // ForceSign через паттерн: +positive;-negative
        $hasForceSign = $this->hasFlag(FormatFlag::ForceSign);

        // Дробная часть
        $fracPrecision = $this->precision ?? 6;
        $fracPart = $fracPrecision > 0
            ? '.' . str_repeat('#', $fracPrecision)
            : '';

        // Целая часть с нулями / группировкой
        if ($this->hasFlag(FormatFlag::ZeroPad) && $this->width !== null) {
            $fracLen = $fracPrecision > 0 ? $fracPrecision + 1 : 0;
            $intDigits = max(1, $this->width - $fracLen);
            $intPart = $hasGroup
                ? '#,##' . str_repeat('0', max(1, $intDigits - 4)) . $fracPart
                : str_repeat('0', $intDigits) . $fracPart;
        } else {
            $intPart = ($hasGroup ? '#,##0' : '#') . $fracPart;
        }

        if ($hasParens) {
            $pattern = $intPart . ';(' . $intPart . ')';
        } elseif ($hasForceSign) {
            $pattern = '+' . $intPart . ';-' . $intPart;
        } else {
            $pattern = $intPart;
        }

        return '{' . self::argName($icuIndex) . ', number, ' . $pattern . '}';
    }
}
