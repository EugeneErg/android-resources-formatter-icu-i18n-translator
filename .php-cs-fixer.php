<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

/**
 * PHP CS Fixer — максимально полный конфиг
 *
 * Документация: https://github.com/PHP-CS-Fixer/PHP-CS-Fixer
 * Список всех правил: https://cs.symfony.com/doc/rules/index.html
 *
 * Требования: php-cs-fixer ^3.0
 */

$finder = Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')          // исключить Blade-шаблоны Laravel
    ->notPath('bootstrap/cache')
    ->notPath('storage')
    ->notPath('vendor')
    ->notPath('node_modules')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->ignoreUnreadableDirs(true);

return (new Config())
    ->setParallelConfig(ParallelConfigFactory::detect()) // автоопределение числа процессов
    ->setRiskyAllowed(true)          // разрешить «рискованные» правила
    ->setUsingCache(true)            // кэшировать результаты
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setIndent('    ')              // 4 пробела
    ->setLineEnding("\n")            // Unix-переносы строк
    ->setFinder($finder)
    ->setRules([

        // ─────────────────────────────────────────────────────────────────────
        // ПРЕСЕТЫ  (перечисляются первыми, отдельные правила ниже переопределяют)
        // ─────────────────────────────────────────────────────────────────────
        '@PSR1'                      => true,
        '@PSR2'                      => true,
        '@PSR12'                     => true,
        '@PSR12:risky'               => true,
        '@PhpCsFixer'                => true,
        '@PhpCsFixer:risky'          => true,
        '@Symfony'                   => true,
        '@Symfony:risky'             => true,
        // '@PHP80Migration'         => true,   // раскомментировать под PHP 8.0
        // '@PHP81Migration'         => true,   // раскомментировать под PHP 8.1
        // '@PHP82Migration'         => true,   // раскомментировать под PHP 8.2
        // '@PHP83Migration'         => true,   // раскомментировать под PHP 8.3
        // '@DoctrineAnnotation'     => true,   // для проектов с Doctrine

        // ─────────────────────────────────────────────────────────────────────
        // АЛИАСЫ / УСТАРЕВШИЕ ФУНКЦИИ (risky)
        // ─────────────────────────────────────────────────────────────────────
        'mb_str_functions'           => false,  // не заменять str_* на mb_* автоматически
        'no_alias_functions'         => ['sets' => ['@all']],
        'no_alias_language_construct_call' => true,
        'no_mixed_echo_print'        => ['use' => 'echo'],

        // ─────────────────────────────────────────────────────────────────────
        // МАССИВЫ
        // ─────────────────────────────────────────────────────────────────────
        'array_indentation'          => true,
        'array_push'                 => true,   // risky: array_push($a,$b) → $a[] = $b
        'array_syntax'               => ['syntax' => 'short'],
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_trailing_comma_in_singleline' => true,
        'normalize_index_brace'      => true,
        'trailing_comma_in_multiline' => [
            'elements'               => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters'],
            'after_heredoc'          => true,
        ],
        'trim_array_spaces'          => true,
        'whitespace_after_comma_in_array' => ['ensure_single_space' => true],

        // ─────────────────────────────────────────────────────────────────────
        // ПРИВЕДЕНИЕ ТИПОВ
        // ─────────────────────────────────────────────────────────────────────
        'cast_spaces'                => ['space' => 'single'],
        'lowercase_cast'             => true,
        'modernize_types_casting'    => true,   // risky: intval() → (int)
        'no_short_bool_cast'         => true,
        'no_unset_cast'              => true,
        'short_scalar_cast'          => true,   // integer → int, boolean → bool

        // ─────────────────────────────────────────────────────────────────────
        // КОММЕНТАРИИ И ДОКУМЕНТАЦИЯ
        // ─────────────────────────────────────────────────────────────────────
        'align_multiline_comment'    => false,//['comment_type' => 'phpdocs_like'],
        'comment_to_phpdoc'          => ['ignored_tags' => ['todo', 'fixme', 'hack']],
        'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'package', 'subpackage']],
        'general_phpdoc_tag_rename'  => ['replacements' => ['inheritDocs' => 'inheritDoc']],
        'multiline_comment_opening_closing' => true,
        'no_blank_lines_after_phpdoc' => true,
        'no_empty_comment'           => true,
        'no_empty_phpdoc'            => true,
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed'            => true,
            'allow_unused_params'    => false,
            'remove_inheritdoc'      => false,
        ],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
        'phpdoc_align'               => [
            'align' => 'left',
            'tags' => ['method', 'param', 'property', 'property-read', 'property-write', 'return', 'throws', 'type', 'var']
        ],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent'              => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_line_span'           => ['const' => 'multi', 'method' => 'multi', 'property' => 'multi'],
        'phpdoc_no_access'           => true,
        'phpdoc_no_alias_tag'        => ['replacements' => ['property-read' => 'property', 'property-write' => 'property', 'type' => 'var', 'link' => 'see']],
        'phpdoc_no_empty_return'     => true,
        'phpdoc_no_package'          => true,
        'phpdoc_no_useless_inheritdoc' => true,
        'phpdoc_order'               => ['order' => ['param', 'return', 'throws']],
        'phpdoc_order_by_value'      => ['annotations' => ['covers', 'dataProvider', 'group', 'internal', 'method', 'mixin', 'property', 'property-read', 'property-write', 'requires', 'throws', 'uses']],
        'phpdoc_param_order'         => true,
        'phpdoc_return_self_reference' => ['replacements' => ['this' => '$this', '@this' => '$this', '$self' => 'self', '@self' => 'self', '$static' => 'static', '@static' => 'static']],
        'phpdoc_scalar'              => ['types' => ['boolean', 'callback', 'double', 'integer', 'real', 'str']],
        'phpdoc_separation'          => ['groups' => [['deprecated', 'link', 'see', 'since'], ['author', 'copyright', 'license'], ['category', 'package', 'subpackage'], ['property', 'property-read', 'property-write']]],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary'             => true,
        'phpdoc_tag_casing'          => ['tags' => ['inheritDoc']],
        'phpdoc_tag_type'            => ['tags' => ['api' => 'annotation', 'author' => 'annotation', 'copyright' => 'annotation', 'deprecated' => 'annotation', 'example' => 'annotation', 'global' => 'annotation', 'inheritDoc' => 'annotation', 'internal' => 'annotation', 'license' => 'annotation', 'method' => 'annotation', 'package' => 'annotation', 'param' => 'annotation', 'property' => 'annotation', 'return' => 'annotation', 'see' => 'annotation', 'since' => 'annotation', 'throws' => 'annotation', 'todo' => 'annotation', 'uses' => 'annotation', 'var' => 'annotation', 'version' => 'annotation']],
        'phpdoc_to_comment'          => false,  // не конвертировать /** */ в /* */ у переменных
        'phpdoc_to_param_type'       => false,  // risky — не добавлять типы из phpdoc в сигнатуры
        'phpdoc_to_property_type'    => false,  // risky
        'phpdoc_to_return_type'      => false,  // risky
        'phpdoc_trim'                => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_types'               => ['groups' => ['simple', 'alias', 'meta']],
        'phpdoc_types_order'         => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'alpha'],
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_var_without_name'    => true,
        'single_line_comment_spacing' => true,
        'single_line_comment_style'  => ['comment_types' => ['hash']],

        // ─────────────────────────────────────────────────────────────────────
        // КЛАССЫ
        // ─────────────────────────────────────────────────────────────────────
        'class_attributes_separation' => [
            'elements' => [
                'const'              => 'one',
                'method'             => 'one',
                'property'           => 'one',
                'trait_import'       => 'none',
                'case'               => 'none',
            ],
        ],
        'class_definition'           => [
            'multi_line_extends_each_single_line' => false,
            'single_item_single_line'             => true,
            'single_line'                         => true,
            'space_before_parenthesis'            => false,
        ],
        'class_keyword'              => false,  // risky — не заменять 'ClassName::class' (изменяет поведение)
        'final_class'                => false,  // не делать все классы final автоматически
        'final_internal_class'       => [       // risky
            'annotation_include'     => ['@internal'],
            'annotation_exclude'     => ['@final', '@Entity', '@ORM'],
            'consider_absent_docblock_as_internal_class' => false,
        ],
        'final_public_method_for_abstract_class' => false,
        'no_blank_lines_after_class_opening' => true,
        'no_null_property_initialization' => true,
        'no_php4_constructor'        => true,   // risky
        'no_unneeded_final_method'   => true,   // risky
        'ordered_class_elements'     => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public_static',
                'property_protected_static',
                'property_private_static',
                'property_public',
                'property_protected',
                'property_private',
                'property_public_readonly',
                'property_protected_readonly',
                'property_private_readonly',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_abstract',
                'method_public_static',
                'method_protected_static',
                'method_private_static',
                'method_public',
                'method_protected',
                'method_private',
                'method_public_abstract_static',
                'method_protected_abstract_static',
            ],
            'sort_algorithm' => 'none',
        ],
        'ordered_interfaces'         => ['direction' => 'ascend', 'order' => 'alpha'],
        'ordered_traits'             => true,   // risky
        'protected_to_private'       => true,   // risky
        'self_accessor'              => true,   // risky: заменить ClassName:: на self::
        'self_static_accessor'       => true,
        'single_class_element_per_statement' => ['elements' => ['const', 'property']],
        'single_trait_insert_per_statement' => true,
        'visibility_required'        => ['elements' => ['const', 'method', 'property']],

        // ─────────────────────────────────────────────────────────────────────
        // КОНСТАНТЫ
        // ─────────────────────────────────────────────────────────────────────
        'constant_case'              => ['case' => 'lower'],  // true/false/null в нижнем регистре
        'native_constant_invocation' => [                     // risky: \PHP_EOL вместо PHP_EOL
            'fix_built_in'           => true,
            'include'                => [],
            'exclude'                => ['null', 'true', 'false'],
            'scope'                  => 'namespaced',
            'strict'                 => true,
        ],

        // ─────────────────────────────────────────────────────────────────────
        // ПОТОКИ УПРАВЛЕНИЯ
        // ─────────────────────────────────────────────────────────────────────
        'control_structure_braces'   => true,
        'control_structure_continuation_position' => ['position' => 'same_line'],
        'elseif'                     => true,
        'empty_loop_body'            => ['style' => 'semicolon'],
        'empty_loop_condition'       => ['style' => 'while'],
        'include'                    => true,
        'no_alternative_syntax'      => true,
        'no_break_comment'           => ['comment_text' => 'no break'],
        'no_superfluous_elseif'      => true,
        'no_unneeded_braces'         => ['namespaces' => true],
        'no_unneeded_control_parentheses' => [
            'statements'             => ['break', 'clone', 'continue', 'echo_print', 'negative_instanceof', 'others', 'return', 'switch_case', 'yield', 'yield_from'],
        ],
        'no_useless_else'            => true,
        'simplified_if_return'       => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space'          => true,
        'switch_continue_to_break'   => true,
        'trailing_comma_in_multiline' => [  // уже объявлено выше — PHP CS Fixer применит последнее
            'elements' => ['arguments', 'array_destructuring', 'arrays', 'match', 'parameters'],
        ],
        'yoda_style'                 => [
            'equal'                  => false,
            'identical'              => false,
            'less_and_greater'       => false,
            'always_move_variable'   => false,
        ],

        // ─────────────────────────────────────────────────────────────────────
        // ФУНКЦИИ
        // ─────────────────────────────────────────────────────────────────────
        'combine_nested_dirname'     => true,   // risky: dirname(dirname($x)) → dirname($x, 2)
        'date_time_create_from_format_call' => true,  // risky
        'date_time_immutable'        => false,  // risky — не заменять DateTime на DateTimeImmutable
        'fopen_flag_order'           => true,   // risky
        'fopen_flags'                => ['b_mode' => false],  // risky
        'function_declaration'       => ['closure_function_spacing' => 'one', 'closure_fn_spacing' => 'one'],
        'function_typehint_space'    => true,
        'implode_call'               => true,   // risky: implode($a, $b) → implode($b, $a)
        'lambda_not_used_import'     => true,
        'method_argument_space'      => [
            'on_multiline'           => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
            'after_heredoc'          => true,
        ],
        'native_function_casing'     => true,
        'native_function_invocation' => [  // risky: \strlen() вместо strlen()
            'include'                => ['@compiler_optimized'],
            'scope'                  => 'namespaced',
            'strict'                 => true,
        ],
        'native_function_type_declaration_casing' => true,
        'no_spaces_after_function_name' => true,
        'no_unreachable_default_argument_value' => true,  // risky
        'no_useless_sprintf'         => true,  // risky
        'nullable_type_declaration_for_default_null_value' => true,
        'phpdoc_to_param_type'       => false, // risky — дублируется выше
        'regular_callable_call'      => true,  // risky: call_user_func($f) → $f()
        'return_type_declaration'    => ['space_before' => 'none'],
        'single_line_throw'          => false, // разрешить многострочные throw
        'static_lambda'              => true,  // risky: static function () {}
        'use_arrow_functions'        => false, // risky — не конвертировать function в fn автоматически
        'void_return'                => true,  // risky

        // ─────────────────────────────────────────────────────────────────────
        // ИМПОРТЫ / USE
        // ─────────────────────────────────────────────────────────────────────
        'fully_qualified_strict_types' => [
            'import_symbols'             => true,
            'leading_backslash_in_global_namespace' => false,
            'phpdoc_tags'                => ['param', 'phpstan-param', 'phpstan-property', 'phpstan-property-read', 'phpstan-property-write', 'phpstan-return', 'phpstan-var', 'property', 'property-read', 'property-write', 'psalm-param', 'psalm-property', 'psalm-property-read', 'psalm-property-write', 'psalm-return', 'psalm-var', 'return', 'see', 'throws', 'var'],
        ],
        'global_namespace_import'    => [
            'import_classes'         => true,
            'import_constants'       => true,
            'import_functions'       => true,
        ],
        'group_import'               => false, // запретить use A\{B, C}
        'no_leading_import_slash'    => true,
        'no_unneeded_import_alias'   => true,
        'no_unused_imports'          => true,
        'ordered_imports'            => [
            'sort_algorithm'         => 'alpha',
            'imports_order'          => ['class', 'function', 'const'],
        ],
        'single_import_per_statement' => ['group_to_single_imports' => true],
        'single_line_after_imports'  => true,

        // ─────────────────────────────────────────────────────────────────────
        // ОПЕРАТОРЫ И ВЫРАЖЕНИЯ
        // ─────────────────────────────────────────────────────────────────────
        'assign_null_coalescing_to_coalesce_equal' => true,
        'binary_operator_spaces'     => [
            'default'                => 'single_space',
            'operators'              => [
                '='  => 'single_space',
                '+=' => 'single_space',
                '|'  => 'no_space',
                '&'  => 'no_space',
            ],
        ],
        'concat_space'               => ['spacing' => 'one'],
        'increment_style'            => ['style' => 'pre'],
        'logical_operators'          => true,   // risky: and/or → &&/||
        'long_to_shorthand_operator' => true,
        'new_with_braces'            => ['anonymous_class' => true, 'named_class' => true],
        'new_with_parentheses'       => ['anonymous_class' => true, 'named_class' => true],
        'not_operator_with_space'    => false,  // не добавлять пробел после !
        'not_operator_with_successor_space' => false,
        'object_operator_without_whitespace' => true,
        'operator_linebreak'         => ['only_booleans' => false, 'position' => 'beginning'],
        'standardize_increment'      => true,
        'standardize_not_equals'     => true,   // != → <>  или наоборот
        'ternary_operator_spaces'    => true,
        'ternary_to_elvis_operator'  => true,   // risky: $a ? $a : $b → $a ?: $b
        'ternary_to_null_coalescing' => true,
        'unary_operator_spaces'      => true,

        // ─────────────────────────────────────────────────────────────────────
        // PHP-ТЕГИ И ЗАГОЛОВКИ ФАЙЛОВ
        // ─────────────────────────────────────────────────────────────────────
        'blank_line_after_opening_tag' => true,
        'declare_equal_normalize'    => ['space' => 'none'],
        'declare_parentheses'        => true,
        'declare_strict_types'       => true,  // risky: добавляет declare(strict_types=1)
        'echo_tag_syntax'            => ['format' => 'long', 'long_function' => 'echo', 'shorten_simple_statements_only' => true],
        'full_opening_tag'           => true,
        'header_comment'             => [       // раскомментировать для добавления заголовка
            'header'                 => '',
            'comment_type'          => 'PHPDoc',
            'location'              => 'after_declare_strict',
            'separate'              => 'both',
        ],
        'linebreak_after_opening_tag' => true,
        'no_closing_tag'             => true,

        // ─────────────────────────────────────────────────────────────────────
        // СТРОКИ
        // ─────────────────────────────────────────────────────────────────────
        'escape_implicit_backslashes' => [
            'double_quoted'          => true,
            'heredoc_syntax'         => true,
            'single_quoted'          => false,
        ],
        'explicit_string_variable'   => true,
        'heredoc_closing_marker'     => true,
        'heredoc_indentation'        => ['indentation' => 'start_plus_one'],
        'heredoc_to_nowdoc'          => true,
        'multiline_string_to_heredoc' => true,
        'no_binary_string'           => true,
        'simple_to_complex_string_variable' => true,
        'single_quote'               => ['strings_containing_single_quote_chars' => false],
        'string_implicit_backslashes' => ['single_quoted' => 'escape'],
        'string_length_to_empty'     => true,  // risky: strlen($a) === 0 → $a === ''

        // ─────────────────────────────────────────────────────────────────────
        // ПРОСТРАНСТВА ИМЁН
        // ─────────────────────────────────────────────────────────────────────
        'clean_namespace'            => true,
//        'no_leading_namespace_separator' => true,

        // ─────────────────────────────────────────────────────────────────────
        // НУЛИ / ПУСТЫЕ КОНСТРУКЦИИ
        // ─────────────────────────────────────────────────────────────────────
        'no_empty_statement'         => true,
        'no_singleline_whitespace_before_semicolons' => true,
        'no_useless_return'          => true,
        'no_useless_nullsafe_operator' => true,
        'nullable_type_declaration'  => ['syntax' => 'union'],  // ?int → int|null (PHP 8+)
        'simplified_null_return'     => true,

        // ─────────────────────────────────────────────────────────────────────
        // ПРОБЕЛЫ И ФОРМАТИРОВАНИЕ
        // ─────────────────────────────────────────────────────────────────────
        'blank_line_before_statement' => [
            'statements'             => ['break', 'case', 'continue', 'declare', 'default', 'do', 'exit', 'for', 'foreach', 'goto', 'if', 'include', 'include_once', 'phpdoc', 'require', 'require_once', 'return', 'switch', 'throw', 'try', 'while', 'yield', 'yield_from'],
        ],
        'blank_lines_before_namespace' => ['min_line_breaks' => 2, 'max_line_breaks' => 2],
        'braces_position'            => [
            'anonymous_classes_opening_brace'        => 'same_line',
            'anonymous_functions_opening_brace'      => 'same_line',
            'classes_opening_brace'                  => 'next_line_unless_newline_at_signature_end',
            'control_structures_opening_brace'       => 'same_line',
            'functions_opening_brace'                => 'next_line_unless_newline_at_signature_end',
            'allow_single_line_anonymous_functions'  => true,
            'allow_single_line_empty_anonymous_classes' => true,
        ],
        'compact_nullable_type_declaration' => true,
        'indentation_type'           => true,
        'line_ending'                => true,
        'method_chaining_indentation' => true,
        'no_extra_blank_lines'       => [
            'tokens'                 => ['attribute', 'break', 'case', 'continue', 'curly_brace_block', 'default', 'extra', 'parenthesis_brace_block', 'return', 'square_brace_block', 'switch', 'throw', 'use', 'use_trait'],
        ],
        'no_spaces_around_offset'    => ['positions' => ['inside', 'outside']],
        'no_spaces_inside_parenthesis' => true,
        'no_trailing_whitespace'     => true,
        'no_trailing_whitespace_in_comment' => true,
        //'no_trailing_whitespace_in_heredoc' => true,
        'no_whitespace_before_comma_in_array' => ['after_heredoc' => true],
        'no_whitespace_in_blank_line' => true,
        'single_blank_line_at_eof'   => true,
        'single_space_around_construct' => [
            'constructs_followed_by_a_single_space'  => ['abstract', 'as', 'attribute', 'break', 'case', 'catch', 'class', 'clone', 'comment', 'const', 'const_import', 'continue', 'do', 'echo', 'else', 'elseif', 'enum', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'function_import', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'match', 'named_argument', 'namespace', 'new', 'open_tag_with_echo', 'php_open', 'print', 'readonly', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'type_colon', 'use', 'use_lambda', 'use_trait', 'var', 'while', 'yield', 'yield_from'],
            'constructs_preceded_by_a_single_space'  => ['as', 'use_lambda'],
        ],
        'spaces_inside_parentheses'  => ['space' => 'none'],
        'statement_indentation'      => true,
        'type_declaration_spaces'    => ['elements' => ['function', 'property']],
        'types_spaces'               => ['space' => 'none', 'space_multiple_catch' => 'none'],

        // ─────────────────────────────────────────────────────────────────────
        // РЕГИСТР
        // ─────────────────────────────────────────────────────────────────────
        'class_reference_name_casing' => true,
        'integer_literal_case'       => true,  // 0x1A → 0x1a, 0B101 → 0b101
        //'keyword_spacing'            => ['default' => 'single'],
        'lowercase_keywords'         => true,
        'lowercase_static_reference' => true,  // Self:: → self::, SELF:: → self::
        'magic_constant_casing'      => true,  // __DIR__ → __DIR__ (нормализация)
        'magic_method_casing'        => true,  // __Construct → __construct
        'native_type_declaration_casing' => true,
        //'uppercase_static_properties' => false,

        // ─────────────────────────────────────────────────────────────────────
        // PHP 7 / PHP 8 ВОЗМОЖНОСТИ
        // ─────────────────────────────────────────────────────────────────────
        'backtick_to_shell_exec'     => true,
        'ereg_to_preg'               => true,   // risky
        'get_class_to_class_keyword' => true,   // risky: get_class($a) → $a::class
        'is_null'                    => true,   // risky: is_null($a) → null === $a
        'list_syntax'                => ['syntax' => 'short'],
        'modernize_strpos'           => true,   // risky: strpos($a,$b) === 0 → str_starts_with()
        'no_useless_concat_operator' => ['juggle_simple_strings' => true],
        'pow_to_exponentiation'      => true,   // risky: pow($a,$b) → $a ** $b
        'random_api_migration'       => ['replacements' => ['getrandmax' => 'mt_getrandmax', 'rand' => 'mt_rand', 'srand' => 'mt_srand']],  // risky
        'set_type_to_cast'           => true,   // risky: settype($a,'int') → $a = (int)$a

        // ─────────────────────────────────────────────────────────────────────
        // АТРИБУТЫ PHP 8
        // ─────────────────────────────────────────────────────────────────────
        'attribute_empty_parentheses' => ['use_parentheses' => false],

        // ─────────────────────────────────────────────────────────────────────
        // ПЕРЕЧИСЛЕНИЯ (PHP 8.1)
        // ─────────────────────────────────────────────────────────────────────
        'no_null_property_initialization' => true,

        // ─────────────────────────────────────────────────────────────────────
        // RETURN / ТИПЫ ВОЗВРАТА
        // ─────────────────────────────────────────────────────────────────────
        'no_useless_return'          => true,
        'return_assignment'          => true,

        // ─────────────────────────────────────────────────────────────────────
        // ТЕСТЫ (PHPUnit)
        // ─────────────────────────────────────────────────────────────────────
        'php_unit_attributes'           => true,   // risky: @test → #[Test]
        'php_unit_construct'            => ['assertions' => ['assertEquals', 'assertNotEquals', 'assertNotSame', 'assertSame']],  // risky
        'php_unit_data_provider_name'   => ['prefix' => 'provide', 'suffix' => 'Cases'],  // risky
        'php_unit_data_provider_return_type' => true,   // risky
        'php_unit_data_provider_static' => ['force' => false],  // risky
        'php_unit_dedicate_assert'      => ['target' => 'newest'],  // risky
        'php_unit_expectation'          => ['target' => 'newest'],  // risky
        'php_unit_fqcn_annotation'      => true,   // risky
        'php_unit_internal_class'       => ['types' => ['abstract', 'final', 'normal']],
        'php_unit_method_casing'        => ['case' => 'camel_case'],
        'php_unit_mock'                 => ['target' => 'newest'],  // risky
        'php_unit_mock_short_will_return' => true,  // risky
        'php_unit_namespaced'           => ['target' => 'newest'],  // risky
        'php_unit_no_expectation_annotation' => ['target' => 'newest', 'use_class_const' => true],  // risky
        'php_unit_set_up_tear_down_visibility' => true,   // risky
        'php_unit_size_class'           => false,
        'php_unit_strict'               => false,  // risky — не заменять assertEquals на assertSame
        'php_unit_test_annotation'      => ['style' => 'annotation'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],  // risky
        'php_unit_test_class_requires_covers' => false,

        // ─────────────────────────────────────────────────────────────────────
        // РАЗНОЕ
        // ─────────────────────────────────────────────────────────────────────
        'combine_consecutive_issets'    => true,
        'combine_consecutive_unsets'    => true,
        'dir_constant'                  => true,  // risky: dirname(__FILE__) → __DIR__
        'error_suppression'             => [      // risky
            //'mute_funcs'              => ['trigger_error', 'user_error'],
            'noise_remaining_usages'  => false,
            'noise_remaining_usages_exclude' => [],
        ],
        'explicit_indirect_variable'    => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        //'no_useless_semicolon'          => true,
        'semicolon_after_instruction'   => true,
    ]);