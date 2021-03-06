<?php

/**
 * Copyright (c) 2017-2020 gyselroth™  (http://www.gyselroth.net)
 *
 * @package \gyselroth\Helper
 * @author  gyselroth™  (http://www.gyselroth.com)
 * @link    http://www.gyselroth.com
 * @license Apache-2.0
 */

namespace Gyselroth\Helper;

use Gyselroth\Helper\Exception\PregExceptionEmptyExpression;
use Gyselroth\Helper\Interfaces\ConstantsDataTypesInterface;
use Gyselroth\Helper\Interfaces\ConstantsEntitiesOfStrings;
use Gyselroth\Helper\Interfaces\ConstantsOperatorsInterface;
use Gyselroth\HelperLog\LoggerWrapper;

class HelperString implements ConstantsDataTypesInterface, ConstantsOperatorsInterface, ConstantsEntitiesOfStrings
{
    public const LOG_CATEGORY = 'stringHelper';

    /**
     * @param      string $haystack
     * @param      array  $needles
     * @return     array|false
     * @deprecated use instead: strposConsecutive()
     */
    public static function strPosMultiple(string $haystack, array $needles) {
        return self::strPosConsecutive($haystack, $needles);
    }

    /**
     * Find successive sub-string offsets
     *
     * @param  string $haystack
     * @param  array  $needles
     * @param  bool   $associative Return as associative array ['needle1' => $offset1, 'needle2' => ...] (default) or as indexed array?
     * @return array|false          Array w/ found offset of each needle or false if none is contained in $haystack
     */
    public static function strPosConsecutive(string $haystack, array $needles, bool $associative = true)
    {
        $offsets     = \array_flip($needles);
        $hasFoundAny = false;

        foreach ($needles as $needle) {
            /** @noinspection ReturnFalseInspection */
            // @todo offsets are not consecutive! check: isn't the following strpos() missing an $offset+1 argument?
            $offset = \strpos($haystack, $needle);

            if (false !== $offset) {
                $offsets[$needle] = $offset;
                $hasFoundAny      = true;
            }
        }

        if (!$hasFoundAny) {
            return false;
        }

        return $associative ? $offsets : \array_values($offsets);
    }

    /**
     * Get string between first occurrence of start and end if both markers are found
     *
     * @param  string $string
     * @param  string $start
     * @param  string $end
     * @param  bool   $trim
     * @return string
     */
    public static function getStringBetween(string $string, string $start, string $end, bool $trim = true): string
    {
        if ('' === $string
            || '' === $start
            || '' === $end
        ) {
            return '';
        }

        /** @noinspection ReturnFalseInspection */
        $offset = \strpos($string, $start);

        /** @noinspection ReturnFalseInspection */
        if (false === $offset
            || false === \strpos($string, $end)
        ) {
            return '';
        }

        $offset  += \strlen($start);

        // @todo check if $offset is not contained, $length will be negative.
        //       check how this can happen and to what consequence, avoid that possible error.
        $length  = \strpos($string, $end, $offset) - $offset;
        $between = \substr($string, $offset, $length);

        return $trim ? \trim($between) : $between;
    }

    /**
     * @param  string       $haystack
     * @param  array|string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, $needle): bool
    {
        if ('' === $needle) {
            return true;
        }

        if (!\is_array($needle)) {
            /** @noinspection ReturnFalseInspection */
            return 0 === \strpos($haystack, $needle);
        }

        // Needle is array (of needles): check whether haystack starts with any of them

        /** @noinspection ForeachSourceInspection */
        foreach ($needle as $needleString) {
            /** @noinspection ReturnFalseInspection */
            if (0 === \strpos($haystack, $needleString)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string       $haystack
     * @param  array|string $needles
     * @return bool         Haystack ends w/ given (or any of the multiple given) needle(s)?
     */
    public static function endsWith(string $haystack, $needles): bool
    {
        if (!\is_array($needles)) {
            return '' === $needles
                || \substr($haystack, -\strlen($needles)) === $needles;
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($needles as $needle) {
            if (self::endsWith($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    public static function replaceFirst(string $subject, string $search, string $replace = ''): string
    {
        if ('' !== $search) {
            /** @noinspection ReturnFalseInspection */
            $offset = \strpos($subject, $search);

            if (false !== $offset) {
                return \substr_replace($subject, $replace, $offset, \strlen($search));
            }
        }

        return $subject;
    }

    public static function replaceLast(string $search, string $replace, string $subject): string
    {
        /** @noinspection ReturnFalseInspection */
        $offset = \strrpos($subject, $search);

        return false !== $offset
            ? \substr_replace($subject, $replace, $offset, \strlen($search))
            : $subject;
    }

    /**
     * @param  string $str
     * @param  string $lhs                   "left-hand-side" (prefix to be prepended)
     * @param  string $rhs                   "right-hand-side" (postfix to be appended)
     * @param  bool   $preventDoubleWrapping Prevent wrapping into already existing LHS / RHS?
     * @return string                        Given string wrapped into given LHS / RHS
     */
    public static function wrap(string $str, string $lhs, string $rhs, bool $preventDoubleWrapping = true): string
    {
        return $preventDoubleWrapping
            ? (static::startsWith($str, $lhs) ? '' : $lhs) . $str . (static::endsWith($str, $rhs) ? '' : $rhs)
            : $lhs . $str . $rhs;
    }

    /**
     * @param  string $needle
     * @param  string $str
     * @param  int    $offsetNeedle
     * @param  bool   $excludeNeedle
     * @return string Substring of given string starting from $offsetNeedle'th occurrence of needle
     */
    public static function removeAllBefore(
        string $needle,
        string $str,
        int $offsetNeedle = 0,
        bool $excludeNeedle = false
    ): string
    {
        if ($offsetNeedle > \strlen($str)) {
            return $str;
        }

        // @todo check: when $needle is not contained after $offsetNeedle, $start will be false
        $start = \strpos($str, $needle, $offsetNeedle);

        return \substr($str, $start + ($excludeNeedle ? \strlen($needle) : 0));
    }

    public static function removeAllAfter(
        string $needle,
        string $str,
        int $offsetNeedle = 0,
        bool $excludeNeedle = false
    ): string
    {
        if ($offsetNeedle > \strlen($str)) {
            return $str;
        }

        /** @noinspection ReturnFalseInspection */
        $start = \strpos($str, $needle, $offsetNeedle);

        return \substr(
            $str,
            0,
            $start + ($excludeNeedle ? 0 : \strlen($needle))
        );
    }

    /**
     * @param  string $str
     * @param  string $lhs
     * @param  string $rhs
     * @param  bool   $removeDelimiters
     * @return string Given string w/o the 1st sub-string enclosed by given left- and right-hand-side delimiters
     */
    public static function removeAllBetween(string $str, string $lhs, string $rhs, $removeDelimiters = true): string
    {
        if ('' === $str) {
            return $str;
        }

        /** @noinspection ReturnFalseInspection */
        $offsetLhs = \strpos($str, $lhs);

        if (false === $offsetLhs) {
            return $str;
        }

        /** @noinspection ReturnFalseInspection */
        $offsetRhs = \strpos($str, $rhs, $offsetLhs + 1);

        /** @noinspection PhpUnreachableStatementInspection */
        if (false === $offsetRhs) {
            return $str;
        }

        $needleLengthWithoutDelimiters = $offsetRhs - ($offsetLhs + \strlen($lhs));

        $needleLength = $needleLengthWithoutDelimiters + ($removeDelimiters
            // W/ delimiters: add length of delimiters
            ? \strlen($lhs) + \strlen($rhs)
            // W/o delimiters
            : 0
        );

        return \substr_replace(
            $str,
            '',
            $offsetLhs + ($removeDelimiters ? 0 : \strlen($lhs)),
            $needleLength
        );
    }

    public static function unwrap(string $str, string $lhs, string $rhs): string
    {
        /** @noinspection ReturnFalseInspection */
        if (0 === \strpos($str, $lhs)) {
            // Remove left-hand-side wrap
            $str = \substr($str, \strlen($lhs));
        }

        if (self::endsWith($str, $rhs)) {
            // Remove right-hand-side wrap
            $str = \substr($str, 0, -\strlen($rhs));
        }

        return $str;
    }

    public static function formatJsonCompatible(string $string): string
    {
        return \str_replace(["\n", "\r", "'"], ['', '', '"'], $string);
    }

    public static function isUtf8(string $str): bool
    {
        return \strlen($str) > \strlen(utf8_decode($str));
    }

    /**
     * Reduce all repetitions of the given character(s) inside the given string to a single occurrence
     *
     * @param  string       $string
     * @param  string|array $characters
     * @return string
     */
    public static function reduceCharRepetitions(string $string, $characters): string
    {
        if (\is_array($characters)) {
            foreach ($characters as $currentCharacter) {
                $string = static::reduceCharRepetitions($string, $currentCharacter);
            }
        } else {
            $double = $characters . $characters;

            /** @noinspection ReturnFalseInspection */
            while (false !== \strpos($string, $double)) {
                $string = \str_replace($double, $characters, $string);
            }
        }

        return $string;
    }

    /**
     * Convert string to camelCase
     *
     * @param  string $string
     * @param  bool   $upperCaseFirstLetter
     * @return string|null
     */
    public static function toCamelCase(string $string, bool $upperCaseFirstLetter = false): ?string
    {
        if ($upperCaseFirstLetter) {
            $string = \ucfirst($string);
        }

        return \preg_replace_callback(
            '/-([a-z])/',
            static function($c) {
                return \strtoupper($c[1]);
            },
            $string
        );
    }

    /**
     * @param  string $camelString
     * @param  string $glue
     * @return string Minus separated path string from given camel-cased string
     */
    public static function getPathFromCamelCase(string $camelString, string $glue = '-'): string
    {
        $string = \preg_replace(
            '/(?!^)[A-Z]{2,}(?=[A-Z][a-z])|[A-Z][a-z]/',
            $glue . '$0',
            \lcfirst($camelString));

        return null === $string
            ? ''
            : \strtolower($string);
    }

    /**
     * Generate random string
     *
     * @param  int    $length
     * @param  bool   $containAlphaLower
     * @param  bool   $containAlphaUpper
     * @param  bool   $containNumbers
     * @param  string $specialChars
     * @param  bool   $eachSpecialCharOnlyOnce
     * @return string
     * @throws \Exception
     */
    public static function getRandomString(
        int $length = 8,
        bool $containAlphaLower = true,
        bool $containAlphaUpper = false,
        bool $containNumbers = true,
        string $specialChars = '',
        bool $eachSpecialCharOnlyOnce = true
    ): string
    {
        $str    = '';
        $offset = 0;

        $charTypes = [];

        if ($containAlphaLower) {
            $charTypes[] = static::CHAR_TYPE_ALPHA_LOWER;
        }

        if ($containAlphaUpper) {
            $charTypes[] = static::CHAR_TYPE_ALPHA_UPPER;
        }

        if ($containNumbers) {
            $charTypes[] = static::CHAR_TYPE_NUMBER;
        }

        if (!empty($specialChars)) {
            $charTypes[] = static::CHAR_TYPE_SPECIAL;
        }

        $amountTypes = \count($charTypes);

        while (\strlen($str) < $length) {
            $typeOffset = $offset % $amountTypes;

            switch ($charTypes[$typeOffset]) {
                case static::CHAR_TYPE_ALPHA_LOWER:
                    $str .= static::getRandomLetter();

                    break;
                case static::CHAR_TYPE_ALPHA_UPPER:
                    $str .= static::getRandomLetter(true);

                    break;
                case static::CHAR_TYPE_NUMBER:
                    $str .= \random_int(0, 9);

                    break;
                case static::CHAR_TYPE_SPECIAL:
                    $specialChar = static::getRandomLetter(false, $specialChars);

                    if ($eachSpecialCharOnlyOnce) {
                        $specialChars = \str_replace($specialChar, '', $specialChars);

                        if (empty($specialChars)) {
                            unset($charTypes[$typeOffset]);
                            $amountTypes--;
                        }
                    }

                    $str .= $specialChar;

                    break;
                default:
                    LoggerWrapper::warning(
                        __CLASS__ . '::' . __FUNCTION__ . " - Unknown char type: {$charTypes[$typeOffset]}",
                        [
                            LoggerWrapper::OPT_CATEGORY => self::LOG_CATEGORY,
                            LoggerWrapper::OPT_PARAMS => $charTypes[$typeOffset]
                        ]
                    );

                    break;
            }

            $offset++;
        }

        return $str;
    }

    /**
     * @param  bool   $upperCase
     * @param  string $pool Pool of allowed random characters
     * @return string
     */
    public static function getRandomLetter(
        bool $upperCase = false,
        string $pool = 'abcdefghijklmnopqrstuvwxyz'
    ): string
    {
        if (1 === \strlen($pool)) {
            return $upperCase ? \strtoupper($pool) : $pool;
        }

        $str = \substr(
            \str_shuffle(
                \str_repeat($pool, 5)
            ),
            0,
            1
        );

        return $upperCase ? \strtoupper($str) : $str;
    }

    /**
     * Generate alphabetical string from given character index:
     *
     * 0  = 'a', 1 = 'b', ...,
     * 25 = 'z'
     * 26 = 'aa' (when index > 25: use character of index mod 25,
     *            repeated as many times as there are modulo "wrap-arounds")
     *
     * @param  int $characterIndex
     * @return string
     */
    public static function toAlpha(int $characterIndex): string
    {
        $letters = \range('a', 'z');

        if ($characterIndex <= 25) {
            return (string)$letters[$characterIndex];
        }

        $dividend       = $characterIndex + 1;
        $alphaCharacter = '';

        while ($dividend > 0) {
            $modulo         = ($dividend - 1) % 26;
            $alphaCharacter = $letters[$modulo] . $alphaCharacter;
            $dividend       = \floor(($dividend - $modulo) / 26);
        }

        return $alphaCharacter;
    }

    /**
     * Encode string with base64 and make it save for using in URLs
     *
     * @param  string $string
     * @return string
     */
    public static function urlSafeB64encode(string $string): string
    {
        return \str_replace(
            ['+', '/', '='],
            ['-', '_', '.'],
            \base64_encode($string)
        );
    }

    public static function urlSafeB64Decode(string $string): string
    {
        $data = \str_replace(
            ['-', '_', '.'],
            ['+', '/', '='],
            $string
        );

        $mod4 = \strlen($data) % 4;

        if ($mod4) {
            $data .= \substr('====', $mod4);
        }

        /** @noinspection ReturnFalseInspection */
        return $data
            ? \base64_decode($data)
            : '';
    }

    /**
     * @param  int|string $value
     * @param  int|string $conditionValue
     * @param  string     $operatorString
     * @param  bool       $strict
     * @return bool
     */
    public static function compareValuesByComparisonOperators(
        $value,
        $conditionValue,
        $operatorString = null,
        bool $strict = false
    ): ?bool
    {
        switch ($operatorString) {
            case self::OPERATOR_LESS_THAN:
                return $value < $conditionValue;
            case self::OPERATOR_LESS_OR_EQUAL:
                return $value <= $conditionValue;
            case self::OPERATOR_GREATER_THAN:
                return $value > $conditionValue;
            case self::OPERATOR_EQUAL:
                if ($strict) {
                    return $value === $conditionValue;
                }

                /** @noinspection TypeUnsafeComparisonInspection */
                return $value == $conditionValue;
            case self::OPERATOR_GREATER_OR_EQUAL:
            default:
                return $value >= $conditionValue;
        }
    }

    /**
     * @param  string       $str
     * @param  array|string $needles
     * @return bool Contains any of 1. (if $needles is string) the characters in $needles, 2. any of the given $needles
     */
    public static function containsAnyOf(string $str, $needles): bool
    {
        if (!\is_array($needles)) {
            $needles = HelperPreg::mb_str_split($needles);
        }

        /** @noinspection ForeachSourceInspection */
        foreach ($needles as $needle) {
            /** @noinspection ReturnFalseInspection */
            if (false !== \strpos($str, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert output of var_dump() into serialized representation
     *
     * @param  string $str
     * @return string
     */
    public static function serialize_dump(string $str): string
    {
        /** @noinspection ReturnFalseInspection */
        if (false === \strpos($str, "\n")) {
            // Add new lines
            $regex = [
                '#(\\[.*?\\]=>)#',
                '#(string\\(|int\\(|float\\(|array\\(|NULL|object\\(|})#',
            ];

            $str = \preg_replace($regex, "\n\\1", $str);

            $str = null === $str
                ? ''
                : \trim($str);
        }

        $serialized = \preg_replace(
            [
                // @todo check regular-expressions: are backslashes unintentionally double-escaped?
                '#^\\040*NULL\\040*$#m',
                '#^\\s*array\\((.*?)\\)\\s*{\\s*$#m',
                '#^\\s*string\\((.*?)\\)\\s*(.*?)$#m',
                '#^\\s*int\\((.*?)\\)\\s*$#m',
                '#^\\s*bool\\(true\\)\\s*$#m',
                '#^\\s*bool\\(false\\)\\s*$#m',
                '#^\\s*float\\((.*?)\\)\\s*$#m',
                '#^\\s*\[(\\d+)\\]\\s*=>\\s*$#m',
                '#\\s*?\\r?\\n\\s*#m',
            ],
            [
                'N',
                'a:\\1:{',
                's:\\1:\\2',
                'i:\\1',
                'b:1',
                'b:0',
                'd:\\1',
                'i:\\1',
                ';'
            ],
            $str
        );

        if (null === $serialized) {
            return '';
        }

        $serialized = \preg_replace_callback(
            '#\\s*\\["(.*?)"\\]\\s*=>#',
            static function($match) {
                return 's:' . \strlen($match[1]) . ':\"' . $match[1] . '\"';
            },
            $serialized
        );

        if (null === $serialized) {
            return '';
        }

        $serialized = \preg_replace_callback(
            '#object\\((.*?)\\).*?\\((\\d+)\\)\\s*{\\s*;#',
            static function($match) {
                return 'O:'
                    . \strlen($match[1]) . ':\"'
                    . $match[1] . '\":'
                    . $match[2] . ':{';
            },
            $serialized
        );

        if (null === $serialized) {
            return '';
        }

        $serialized = \preg_replace(['#};#', '#{;#'], ['}', '{'], $serialized);

        return null == $serialized
            ? ''
            : $serialized;
    }

    /**
     * Convert output of var_dump() back into PHP value
     *
     * @param  string $str
     * @return array|bool|float|int|Object|string
     */
    public static function unVar_dump(string $str)
    {
        $serialized = self::serialize_dump($str);

        /** @noinspection UnserializeExploitsInspection */
        return \unserialize($serialized);
    }

    /**
     * Mulit-byte unserialize
     *
     * Fix for PHP SPL unserialize() failing with serialized data containing UTF-8
     * Background: SPL unserialize() calculates total length of serialized data containing multi-byte characters wrong
     *
     * @param string $string
     * @return array|boolean|float|integer|object|string
     */
    public static function mb_unserialize(string $string)
    {
        // special handling for asterisk wrapped in zero bytes
        $string = \str_replace("\0*\0", "*\0", $string);

        $string = \preg_replace_callback(
            '#s:\d+:"(.*?)";#s',
            function ($matches) {
                return \sprintf('s:%d:"%s";', \strlen($matches[1]), $matches[1]);
            },
            $string
        );

        if (null === $string) {
            return '';
        }

        $string = \str_replace('*\0', "\0*\0", $string);

        /** @noinspection UnserializeExploitsInspection */
        return \unserialize($string);
    }

    public static function explodeTrimmed(string $string, string $delimiter = ','): array
    {
        $items = \explode($delimiter, $string);

        if (false ===  $items) {
            return [];
        }

        return \array_map('trim', $items);
    }

    public static function formatBytes(int $size): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base  = \log($size) / \log(1024);

        return \round(1024 ** ($base - \floor($base)), 1) . ' ' . $units[(int)\floor($base)];
    }

    public static function umlautsToAscii(string $str): string
    {
        return \str_replace(
            self::UMLAUTS,
            ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'],
            $str
        );
    }

    public static function translate(string $message, array $args = [], bool $escapeHtmlEntities = false): string
    {
        if ($escapeHtmlEntities) {
            $message = \htmlentities($message, ENT_QUOTES);
        }

        return [] === $args
            ? $message
            : \vsprintf($message, $args);
    }

    public static function translatePlural(string $single, string $multiple, int $amount): string
    {
        return $amount === 0
        || $amount > 1
            ? $multiple
            : $single;
    }

    public static function replaceSpecialCharacters(string $str, bool $toLower = true): string
    {
        $replacePairs = [
            'š' => 's', 'ð' => 'dj', 'ž' => 'z', 'ä' => 'ae', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a',
            'å' => 'a', 'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i', 'ñ' => 'n', 'ö' => 'oe', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o',
            'ü' => 'ue', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f', 'ß' => 'ss'
        ];

        if ($toLower) {
            $str = \strtolower($str);
        } else {
            // Needs to translate also upper-case characters
            $replacePairs = \array_merge(
                $replacePairs,
                [
                    'Š' => 'S', 'Ð' => 'DJ', 'Ž' => 'Z', 'Ä' => 'AE', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
                    'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E',  'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I',
                    'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ö' => 'OE', 'Ò' => 'O',  'Ó' => 'O', 'Ô' => 'O',
                    'Õ' => 'O', 'Ø' => 'O', 'Ü' => 'UE', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U',  'Ý' => 'Y', 'Þ' => 'B',
                    'Ÿ' => 'Y', 'Ƒ' => 'F',
                ]
            );
        }

        return \strtr($str, $replacePairs);
    }

    /**
     * @param  string $csv
     * @param  string $delimiter
     * @return int
     * @todo harden: add optional handling for inline delimiters, e.g. "\"foo, bar\", \"baz, qux\""
     *               should than return 2 instead of 4
     */
    public static function countItemsInCsv(string $csv, string $delimiter = ','): int
    {
        return \substr_count($csv, $delimiter) + 1;
    }

    public static function specialCharsToAscii(string $string, bool $toLower): string
    {
        $replacePairs = [
            'š' => 's', 'ð' => 'dj', 'ž' => 'z', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'æ' => 'a',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y', 'ƒ' => 'f',
            'ß' => 'ss'
        ];

        if ($toLower) {
            $string = \strtolower($string);
        } else {
            // Needs to translate also upper-case characters
            $replacePairs = \array_merge($replacePairs, [
                'Š' => 'S', 'Ð' => 'DJ', 'Ž' => 'Z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'A',
                'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I',
                'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ø' => 'O',
                'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'Ÿ' => 'Y', 'Ƒ' => 'F'
            ]);
        }

        $string = \strtr($string, $replacePairs);

        return \str_replace(
            ['&', '@', '#'],
            ['-and-', '-at-', '-number-'],
            $string
        );
    }

    /**
     * Make both, first and last char of given string lowercase
     *
     * @param string $str
     * @return string
     */
    public static function lowerFirstAndLast(string $str): string
    {
        $str = \strrev($str);
        $str = \lcfirst($str);
        $str = \strrev($str);

        return \lcfirst($str);
    }

    public static function isBase64encodedString(string $str): bool
    {
        return false !== \base64_decode($str);
    }

    public static function isHexadecimalHash(string $str, int $minLen = 0): bool
    {
        return (0 === $minLen || \strlen($str) >= $minLen)
            && '' === \preg_replace('/[0-9a-f]/i', '', $str);
    }

    public static function compressHtml(string $html): string
    {
        $search = [
            '/\>[^\S ]+/s',      // strip whitespaces after tags, except space
            '/[^\S ]+\</s',      // strip whitespaces before tags, except space
            '/(\s)+/s',          // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/'  // remove HTML comments
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];

        return \preg_replace($search, $replace, $html);
    }
}
