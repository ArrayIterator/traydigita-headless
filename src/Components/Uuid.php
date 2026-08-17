<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use Throwable;
use TrayDigita\WP\Headless\Resource\Utils\Filter;
use TrayDigita\WP\Headless\Resource\Utils\Random;
use function bin2hex;
use function chr;
use function dechex;
use function floor;
use function function_exists;
use function hexdec;
use function implode;
use function md5;
use function microtime;
use function preg_match;
use function sha1;
use function sprintf;
use function str_pad;
use function str_replace;
use function str_split;
use function strlen;
use function substr;
use const STR_PAD_LEFT;

/**
 * UUID class to generate and parse UUID.
 * Supports UUID 1 - 8
 *
 * Usage:
 * - Generate UUID v1: UUID::v1() or UUID::generate(1), UUID::generate(1, UUID::UUID_VARIANT_DCE, UUID::UUID_TYPE_TIME)
 * - Generate UUID v2: UUID::v2() or UUID::generate(2), UUID::generate(2, UUID::UUID_VARIANT_DCE, UUID::UUID_TYPE_TIME)
 * - Generate UUID v3:
 *      UUID::v3(UUID::NAMESPACE_DNS, 'www.example.com')
 *      or UUID::generate(3, UUID::UUID_VARIANT_RFC4122, UUID::UUID_TYPE_MD5, UUID::NAMESPACE_DNS, 'www.example.com')
 * - Generate UUID v4:
 *      UUID::v4()
 *      or UUID::generate(4), UUID::generate(4, UUID::UUID_VARIANT_RFC4122, UUID::UUID_TYPE_RANDOM)
 * - Generate UUID v5:
 *      UUID::v5(UUID::NAMESPACE_DNS, 'www.example.com')
 *      or UUID::generate(5, UUID::UUID_VARIANT_RFC4122, UUID::UUID_TYPE_SHA1, UUID::NAMESPACE_DNS, 'www.example.com')
 * - Generate UUID v8: UUID::v8() or UUID::generate(8)
 * - Parse UUID: UUID::parse('550e8400-e29b-41d4-a716-446655440000')
 * - Get UUID version: UUID::version('550e8400-e29b-41d4-a716-446655440000')
 * - Get UUID integer id: UUID::integerId('550e8400-e29b-41d4-a716-446655440000')
 * - Check if a string is a valid UUID: UUID::isValid('550e8400-e29b-41d4-a716-446655440000')
 * - Extract UUID: UUID::extractUUID('550e8400-e29b-41d4-a716-446655440000')
 * - Extract UUID part: UUID::extractUUIDPart('550e8400-e29b-41d4-a716-446655440000')
 * - Calculate namespace and name:
 *      UUID::calculateNamespaceAndName(UUID::NAMESPACE_DNS, 'www.example.com', UUID::UUID_TYPE_MD5)
 *
 */
class Uuid implements Stringable
{
    public const UUID_VERSION_1 = 1;

    public const UUID_VERSION_2 = 2;

    public const UUID_VERSION_3 = 3;

    public const UUID_VERSION_4 = 4;

    public const UUID_VERSION_5 = 5;

    public const UUID_VERSION_6 = 6;

    public const UUID_VERSION_7 = 7;

    public const UUID_VERSION_8 = 8;

    /* ----------------------------------------------------------------------
     * UUID Types
     * ----------------------------------------------------------------------
     */
    public const UUID_TYPE_TIME = 1;

    public const UUID_TYPE_MD5 = 2;

    public const UUID_TYPE_SHA1 = 3;

    public const UUID_TYPE_RANDOM = 4;

    /* ----------------------------------------------------------------------
     * UUID Variant
     * ----------------------------------------------------------------------
     */
    // NCS backward compatibility (with the obsolete Apollo Network Computing System 1.5 UUID format)
    // is: 0 - 7 (0x0 - 0x7)
    public const UUID_VARIANT_NCS = 0;

    // DCE 1.1, ISO/IEC 11578:1996 is: 128 - 191 (0x80 - 0xbf)
    public const UUID_VARIANT_DCE = 1;

    //  microsoft is 192 - 223 (0xc0 - 0xdf)
    public const UUID_VARIANT_MICROSOFT = 2;

    // reserved for future definition is: 224 - 255 (0xe0 - 0xff)
    public const UUID_VARIANT_RESERVED_FUTURE = 3;

    // RFC 4122 / RFC 9562 IETF (OSF DCE) variant is 128 - 191 (0x80 - 0xbf).
    public const UUID_VARIANT_RFC4122 = 4;

    public const VARIANT_NCS = 0x00;

    public const VARIANT_DCE = 0x80;

    public const VARIANT_MICROSOFT = 0xc0;

    public const VARIANT_RESERVED_FUTURE = 0xe0;

    // RFC 4122 / RFC 9562 use the same 10xx variant bits as DCE 1.1.
    public const VARIANT_RFC4122 = 0x80;

    /* ----------------------------------------------------------------------
     * UUID namespace constants for UUID::calculateNamespaceAndName()
     * ----------------------------------------------------------------------
     * https://tools.ietf.org/html/rfc4122#appendix-C
     */
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /* ------------------------------------------------------------------------
     * Variant Name Constants
     * ------------------------------------------------------------------------
     */
    public const DCE_VERSION_NAME = 'DCE 1.1, ISO/IEC 11578:1996';

    public const MICROSOFT_VERSION_NAME = 'Microsoft Corporation GUID';

    public const NCS_VERSION_NAME = 'RESERVED, NCS backward compatibility';

    public const RFC4122_VERSION_NAME = 'RFC 4122, IETF';

    public const RESERVED_FUTURE_VERSION_NAME = 'RESERVED, future definition';

    /**
     * UUID variants for UUID::UUID_VARIANT_* constants
     */
    public const UUID_VARIANTS = [
        self::UUID_VARIANT_NCS => self::VARIANT_NCS,
        self::UUID_VARIANT_DCE => self::VARIANT_DCE,
        self::UUID_VARIANT_RFC4122 => self::VARIANT_RFC4122,
        self::UUID_VARIANT_MICROSOFT => self::VARIANT_MICROSOFT,
        self::UUID_VARIANT_RESERVED_FUTURE => self::VARIANT_RESERVED_FUTURE,
    ];

    /**
     * UUID variant names for UUID::UUID_VARIANT_* constants
     */
    public const UUID_VARIANT_NAMES = [
        self::UUID_VARIANT_NCS => self::NCS_VERSION_NAME,
        self::UUID_VARIANT_DCE => self::DCE_VERSION_NAME,
        self::UUID_VARIANT_MICROSOFT => self::MICROSOFT_VERSION_NAME,
        self::UUID_VARIANT_RFC4122 => self::RFC4122_VERSION_NAME,
        self::UUID_VARIANT_RESERVED_FUTURE => self::RESERVED_FUTURE_VERSION_NAME,
    ];

    /**
     * Single prefix variant for UUID v1
     * The prefix getting from the first character of the 15th character of UUID v1
     */
    public const SINGLE_PREFIX_VARIANT = [
        '0' => self::UUID_VARIANT_NCS,
        '1' => self::UUID_VARIANT_NCS,
        '2' => self::UUID_VARIANT_NCS,
        '3' => self::UUID_VARIANT_NCS,
        '4' => self::UUID_VARIANT_NCS,
        '5' => self::UUID_VARIANT_NCS,
        '6' => self::UUID_VARIANT_NCS,
        '7' => self::UUID_VARIANT_NCS,
        '8' => self::UUID_VARIANT_DCE,
        '9' => self::UUID_VARIANT_DCE,
        'a' => self::UUID_VARIANT_DCE,
        'b' => self::UUID_VARIANT_DCE,
        'c' => self::UUID_VARIANT_MICROSOFT,
        'd' => self::UUID_VARIANT_MICROSOFT,
        'e' => self::UUID_VARIANT_RESERVED_FUTURE,
        'f' => self::UUID_VARIANT_RESERVED_FUTURE,
    ];

    public function __construct()
    {
    }

    /**
     * Get version from uuid
     *
     * @param string $uuid
     * @return int|null null if not valid uuid
     */
    public function version(string $uuid): ?int
    {
        // a0eebc99-9c0b-11d1-0000-000000000000
        if (!$this->isValid($uuid)) {
            return null;
        }
        $uuidDetails = $this->extractUUID($uuid);
        return $uuidDetails['version'] ?? null;
    }

    /**
     * Extract uuid:
     * 1. time low
     * 2. time mid
     * 3. time hi and version
     * 4. clock seq hi and reserved
     * 5. clock seq low
     * 6. node
     * 7. version
     * 8. variant
     * 9. variant name
     *
     * @param string $uuid
     * @return ?array{
     *     time_low: int,
     *     time_mid: int,
     *     time_hi_and_version: int,
     *     clock_seq_hi_and_reserved: int,
     *     clock_seq_low: int,
     *     node: int,
     *     version: int,
     *     variant: int,
     *     variant_name: string,
     * } null if not valid uuid
     */
    public function extractUUID(string $uuid): ?array
    {
        $matches = $this->extractUUIDPart($uuid);
        if (!$matches) {
            return null;
        }
        $timeLow = hexdec($matches[1]);
        $timeMid = hexdec($matches[2]);
        $timeHiAndVersion = hexdec($matches[3]);
        $clockSeqHiAndReserved = hexdec($matches[4]);
        $clockSeqLow = hexdec(substr($matches[4], 2));
        $node = hexdec($matches[5]);
        $variant = substr($matches[4], 0, 1);
        // uuid is hex: 0-9, a-f
        // by default, variant is NCS backward compatibility
        $variant = $this->SINGLE_PREFIX_VARIANT[$variant] ?? self::UUID_VARIANT_NCS;
        $variantName = self::UUID_VARIANT_NAMES[$variant] ?? self::NCS_VERSION_NAME;
        return [
            'time_low' => $timeLow,
            'time_mid' => $timeMid,
            'time_hi_and_version' => $timeHiAndVersion,
            'clock_seq_hi_and_reserved' => $clockSeqHiAndReserved,
            'clock_seq_low' => $clockSeqLow,
            'node' => $node,
            'version' => $timeHiAndVersion >> 12,
            'variant' => $variant,
            'variant_name' => $variantName,
        ];
    }

    /**
     * Extract uuid part:
     * 1. time low
     * 2. time mid
     * 3. time hi and version
     * 4. clock seq hi and reserved
     * 5. clock seq low
     * 6. node
     *
     * @param string $uuid
     * @return ?array
     */
    public function extractUUIDPart(string $uuid): ?array
    {
        preg_match(
            '/^([0-9a-f]{8})-([0-9a-f]{4})-([1-8][0-9a-f]{3})-([0-9a-f]{4})-([0-9a-f]{12})$/i',
            $uuid,
            $matches
        );
        return $matches ?: null;
    }

    /**
     * Check if a string is a valid UUID.
     *
     * @param string $uuid
     * @return bool
     */
    public function isValid(string $uuid): bool
    {
        return $this->extractUUIDPart($uuid) !== null;
    }

    /**
     * Multiply two integers as strings and return the result as a string.
     *
     * @param int|string $int1 The first integer to multiply.
     * @param int|string $int2 The second integer to multiply.
     * @return string The result of the multiplication as a string.
     */
    public function multiply(int|string $int1, int|string $int2): string
    {
        if (function_exists('gmp_mul') && function_exists('gmp_strval')) {
            $gmp = gmp_mul($int1, $int2);
            return gmp_strval($gmp);
        }

        $int1 = (string)$int1;
        $int2 = (string)$int2;
        if (function_exists('bcmul')) {
            return bcmul($int1, $int2);
        }
        $x = strlen($int1);
        $y = strlen($int2);
        $maxDigits = PHP_INT_SIZE === 4 ? 9 : 18;
        $maxDigits = intdiv($maxDigits, 2);
        $complement = 10 ** $maxDigits;

        $result = '0';

        for ($i = $x - $maxDigits;; $i -= $maxDigits) {
            $blockALength = $maxDigits;

            if ($i < 0) {
                $blockALength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            $blockA = (int)substr($int1, $i, $blockALength);

            $line = '';
            $carry = 0;

            for ($j = $y - $maxDigits;; $j -= $maxDigits) {
                $blockBLength = $maxDigits;

                if ($j < 0) {
                    $blockBLength += $j;
                    /** @psalm-suppress LoopInvalidation */
                    $j = 0;
                }

                $blockB = (int)substr($int2, $j, $blockBLength);

                $mul = $blockA * $blockB + $carry;
                $value = $mul % $complement;
                $carry = ($mul - $value) / $complement;

                $value = (string)$value;
                $value = str_pad($value, $maxDigits, '0', STR_PAD_LEFT);

                $line = $value . $line;

                if ($j === 0) {
                    break;
                }
            }

            if ($carry !== 0) {
                $line = $carry . $line;
            }

            $line = ltrim($line, '0');

            if ($line !== '') {
                $line .= str_repeat('0', $x - $blockALength - $i);
                $result = $this->add($result, $line);
            }

            if ($i === 0) {
                break;
            }
        }
        return $result;
    }

    /**
     * Pads the left of one of the given numbers with zeros if necessary to make both numbers the same length.
     *
     * The numbers must only consist of digits, without leading minus sign.
     *
     * @return array{string, string, int}
     */
    private function padNumber(string $a, string $b): array
    {
        $x = strlen($a);
        $y = strlen($b);

        if ($x > $y) {
            $b = str_repeat('0', $x - $y) . $b;

            return [$a, $b, $x];
        }

        if ($x < $y) {
            $a = str_repeat('0', $y - $x) . $a;

            return [$a, $b, $y];
        }

        return [$a, $b, $x];
    }

    /**
     * Add two integers as strings and return the result as a string.
     *
     * @param int|string $a The first integer to add.
     * @param int|string $b The second integer to add.
     * @return string The result of the addition as a string.
     */
    public function add(int|string $a, int|string $b): string
    {
        if (function_exists('gmp_add') && function_exists('gmp_strval')) {
            $res = gmp_add($a, $b);
            return gmp_strval($res);
        }
        $a = (string)$a;
        $b = (string)$b;
        if (function_exists('bcadd')) {
            return bcadd($a, $b);
        }
        $a = Filter::number($a);
        $b = Filter::number($b);

        $maxDigits = PHP_INT_SIZE === 4 ? 9 : 18;
        [$a, $b, $length] = $this->padNumber($a, $b);

        $carry = 0;
        $result = '';

        for ($i = $length - $maxDigits;; $i -= $maxDigits) {
            $blockLength = $maxDigits;

            if ($i < 0) {
                $blockLength += $i;
                /** @psalm-suppress LoopInvalidation */
                $i = 0;
            }

            /** @var numeric $blockA */
            $blockA = substr($a, $i, $blockLength);

            /** @var numeric $blockB */
            $blockB = substr($b, $i, $blockLength);

            $sum = (string)($blockA + $blockB + $carry);
            $sumLength = strlen($sum);

            if ($sumLength > $blockLength) {
                $sum = substr($sum, 1);
                $carry = 1;
            } else {
                if ($sumLength < $blockLength) {
                    $sum = str_repeat('0', $blockLength - $sumLength) . $sum;
                }
                $carry = 0;
            }

            $result = $sum . $result;

            if ($i === 0) {
                break;
            }
        }

        if ($carry === 1) {
            $result = '1' . $result;
        }

        return $result;
    }

    /**
     * Get UUID integer id.
     *
     * @param string $uuid UUID to convert
     * @return ?numeric-string unsigned numeric string known as single integer value or null if not valid uuid
     */
    public function integerId(string $uuid): ?string
    {
        if (!$this->isValid($uuid)) {
            return null;
        }

        // remove hyphens
        $hex = str_replace('-', '', $uuid);
        // convert hex to decimal
        $dec = '0';
        // get length of hex
        $length = strlen($hex);
        // loop hex
        // using bcmul() && bcadd() binary calculator function & prevent loss of precision
        for ($i = 0; $i < $length; $i++) {
            // get the char from hex at position $i
            // -> bcmul the decimal by 16
            $dec = $this->multiply($dec, 16);
            // -> bcadd the decimal by the integer value of the hex char
            $dec = $this->add($dec, hexdec($hex[$i]));
        }

        return $dec;
    }

    /**
     * Parse the uuid and show the detail of:
     *
     * 1. Single Integer (64 bits) (big endian) from UUID
     * 2. Version
     * 3. Variant
     * 4. Timestamp ISO 8601
     * 6. Node / Contents
     * The contents (and content node for version 1) are per hex string separated by (:)
     * @param string $uuid UUID to parse
     *
     * @return ?array{
     *     uuid: string,
     *     single_integer: string,
     *     version: int,
     *     variant: int,
     *     variant_name: string,
     *     contents_node: string,
     *     contents_time: ?string,
     *     contents_clock: int,
     *     contents: string,
     *     time_low: int,
     *     time_mid: int,
     *     time_hi_and_version: int,
     *     clock_seq_hi_and_reserved: int,
     *     clock_seq_low: int,
     *     node: int,
     * } null if not valid uuid if the version is not 1 the time will be null
     */
    public function parse(string $uuid): ?array
    {
        $uuidDetails = $this->extractUUID($uuid);
        if (!$uuidDetails) {
            return null;
        }

        $version = $uuidDetails['version'];
        $timestamp = null;

        if ($version === self::UUID_VERSION_1
            || $version === self::UUID_VERSION_6
        ) {
            if (PHP_INT_SIZE >= 8) {
                if ($version === self::UUID_VERSION_1) {
                    $gregorianTimestamp = (
                        (($uuidDetails['time_hi_and_version'] & 0x0fff) << 48)
                        | ($uuidDetails['time_mid'] << 32)
                        | $uuidDetails['time_low']
                    );
                } else {
                    $gregorianTimestamp = (
                        ($uuidDetails['time_low'] << 28)
                        | ($uuidDetails['time_mid'] << 12)
                        | ($uuidDetails['time_hi_and_version'] & 0x0fff)
                    );
                }

                $unixTimestamp = (
                        $gregorianTimestamp - 0x01B21DD213814000
                    ) / 10000000;

                try {
                    $timestamp = (new DateTime(
                        '@' . (int)$unixTimestamp
                    ))->format(DateTimeInterface::ATOM);
                } catch (Throwable) {
                }
            }
        } elseif ($version === self::UUID_VERSION_7) {
            $unixTimestampMs = (
                ($uuidDetails['time_low'] << 16)
                | $uuidDetails['time_mid']
            );

            try {
                $timestamp = (new DateTime(
                    '@' . intdiv($unixTimestampMs, 1000)
                ))->format(DateTimeInterface::ATOM);
            } catch (Throwable) {
            }
        }

        $clock = ($uuidDetails['clock_seq_hi_and_reserved'] << 8)
            | $uuidDetails['clock_seq_low'];
        $clock &= 0x3fff;

        $node = str_pad(
            dechex($uuidDetails['node']),
            12,
            '0',
            STR_PAD_LEFT
        );
        $node = implode(':', str_split($node, 2));

        $contents = implode(
            ':',
            str_split(str_replace('-', '', $uuid), 2)
        );

        return [
            'uuid' => $uuid,
            'single_integer' => $this->integerId($uuid),
            'version' => $version,
            'variant' => $uuidDetails['variant'],
            'variant_name' => $uuidDetails['variant_name'],
            'contents_node' => $node,
            'contents_time' => $timestamp,
            'contents_clock' => $clock,
            'contents' => $contents,
            'time_low' => $uuidDetails['time_low'],
            'time_mid' => $uuidDetails['time_mid'],
            'time_hi_and_version' => $uuidDetails['time_hi_and_version'],
            'clock_seq_hi_and_reserved' => $uuidDetails['clock_seq_hi_and_reserved'],
            'clock_seq_low' => $uuidDetails['clock_seq_low'],
            'node' => $uuidDetails['node'],
        ];
    }

    /**
     * Calculate namespace and name.
     *
     * @param string $namespace namespace to calculate is uuid
     * @param string $name name to calculate
     * @param ?int $algorithm UUID::UUID_TYPE_MD5 or UUID::UUID_TYPE_SHA1 default is UUID::UUID_TYPE_SHA1
     * @return string calculated namespace and name
     */
    public function calculateNamespaceAndName(
        string $namespace,
        string $name,
        ?int $algorithm = null
    ): string {
        $version = $this->version($namespace);
        if ($version === null) {
            throw new InvalidArgumentException(
                'Invalid namespace'
            );
        }
        if (($algorithm !== self::UUID_TYPE_MD5 && $algorithm !== self::UUID_TYPE_SHA1)) {
            $algorithm = $version === 3 ? self::UUID_TYPE_MD5 : self::UUID_TYPE_SHA1;
        }
        // fallback to sha1 if algorithm is not valid
        $algorithm = $algorithm ?? self::UUID_TYPE_SHA1;
        // Get hexadecimal components of namespace
        $nHex = str_replace(['-', '{', '}'], '', $namespace);
        // Binary Value
        $nStr = '';
        // Convert Namespace UUID to bits
        for ($i = 0, $len = strlen($nHex); $i < $len; $i += 2) {
            $nStr .= chr(hexdec($nHex[$i] . $nHex[$i + 1]));
        }
        // Calculate hash value
        return $algorithm === self::UUID_TYPE_MD5 ? md5($nStr . $name) : sha1($nStr . $name);
    }

    /**
     * Generate a UUID.
     *
     * @param int $version 1, 2, 3, 4, 5, 6, 7, or 8
     * @param ?int $variant UUID variant to use UUID::UUID_VARIANT_* constants
     * @param ?int $type UUID type to use UUID::UUID_TYPE_* constants
     * @param string|null $hash
     * @param int|null $node the (maximum 48-bit) integer node (commonly mac address - hexdec($macHex))
     * @param string|null $custom optional 128-bit hexadecimal custom payload for UUID v8
     * @return string UUID v1, v3, v4, v5, v6, v7, or v8
     */
    public function generate(
        int $version,
        ?int $variant = null,
        ?int $type = null,
        ?string $hash = null,
        ?int $node = null,
        ?string $custom = null
    ): string {
        if (!in_array(
            $version,
            [
                self::UUID_VERSION_1,
                self::UUID_VERSION_2,
                self::UUID_VERSION_3,
                self::UUID_VERSION_4,
                self::UUID_VERSION_5,
                self::UUID_VERSION_6,
                self::UUID_VERSION_7,
                self::UUID_VERSION_8,
            ],
            true
        )) {
            throw new InvalidArgumentException(
                'Unsupported UUID version: ' . $version
            );
        }

        if ($variant === null) {
            $variant = match ($version) {
                self::UUID_VERSION_1,
                self::UUID_VERSION_2 => self::UUID_VARIANT_DCE,
                default => self::UUID_VARIANT_RFC4122,
            };
        }

        if (!array_key_exists($variant, self::UUID_VARIANTS)) {
            throw new InvalidArgumentException(
                'Unsupported UUID variant: ' . $variant
            );
        }

        if (($version === self::UUID_VERSION_6
                || $version === self::UUID_VERSION_7
                || $version === self::UUID_VERSION_8)
            && $variant !== self::UUID_VARIANT_RFC4122
        ) {
            throw new InvalidArgumentException(
                'UUID v6, v7, and v8 require the RFC 9562 variant'
            );
        }

        if ($type === null) {
            $type = match ($version) {
                self::UUID_VERSION_1,
                self::UUID_VERSION_2,
                self::UUID_VERSION_6,
                self::UUID_VERSION_7 => self::UUID_TYPE_TIME,
                self::UUID_VERSION_3 => self::UUID_TYPE_MD5,
                self::UUID_VERSION_5 => self::UUID_TYPE_SHA1,
                self::UUID_VERSION_4,
                self::UUID_VERSION_8 => self::UUID_TYPE_RANDOM,
            };
        }

        if (!in_array(
            $type,
            [
                self::UUID_TYPE_TIME,
                self::UUID_TYPE_MD5,
                self::UUID_TYPE_SHA1,
                self::UUID_TYPE_RANDOM,
            ],
            true
        )) {
            throw new InvalidArgumentException(
                'Unsupported UUID type: ' . $type
            );
        }

        /*
         * v6 and v7 are time-based. Do not let an explicit MD5/SHA-1
         * type silently turn them into a different UUID format.
         */
        if (($version === self::UUID_VERSION_6 || $version === self::UUID_VERSION_7)
            && $type !== self::UUID_TYPE_TIME
            && $type !== self::UUID_TYPE_RANDOM
        ) {
            throw new InvalidArgumentException(
                'UUID v6 and v7 require a time or random generation type'
            );
        }

        /*
         * UUIDv8 is custom by definition. This implementation defines its
         * custom payload as 122 random/custom bits, with an optional
         * 128-bit hexadecimal payload supplied through $custom. The version
         * and variant bits are always replaced by the required RFC 9562 bits.
         */
        if ($version === self::UUID_VERSION_8) {
            if ($custom === null) {
                $custom = bin2hex(Random::bytes(16));
            }

            if (strlen($custom) !== 32
                || !Filter::hex($custom)
            ) {
                throw new InvalidArgumentException(
                    'UUID v8 custom data must be exactly 32 hexadecimal characters'
                );
            }

            $timeLow = hexdec(substr($custom, 0, 8)) & 0xffffffff;
            $timeMid = hexdec(substr($custom, 8, 4)) & 0xffff;
            $timeHi = hexdec(substr($custom, 12, 4)) & 0x0fff;
            $clockSeqHi = hexdec(substr($custom, 16, 2)) & 0x3f;
            $clockSeqLow = hexdec(substr($custom, 18, 2)) & 0xff;
            $node = hexdec(substr($custom, 20, 12))
                & 0xffffffffffff;
        } elseif ($version === self::UUID_VERSION_6
            || $version === self::UUID_VERSION_7
        ) {
            if (PHP_INT_SIZE < 8) {
                throw new InvalidArgumentException(
                    'UUID v6/v7 generation requires a 64-bit PHP runtime'
                );
            }

            if ($version === self::UUID_VERSION_6) {
                $timestamp = (int)(microtime(true) * 10000000)
                    + 0x01B21DD213814000;

                $timeLow = ($timestamp >> 28) & 0xffffffff;
                $timeMid = ($timestamp >> 12) & 0xffff;
                $timeHi = $timestamp & 0x0fff;

                $clockSeq = Random::int(0, 0x3fff);
                $clockSeqHi = ($clockSeq >> 8) & 0x3f;
                $clockSeqLow = $clockSeq & 0xff;

                if ($node === null) {
                    $node = Random::int(0, 0xffffffffffff)
                        | 0x010000000000;
                }

                if ($node < 0 || $node > 0xffffffffffff) {
                    throw new InvalidArgumentException(
                        'UUID v6 node must be a 48-bit unsigned integer'
                    );
                }

                $node &= 0xffffffffffff;
            } else {
                /*
                 * UUIDv7:
                 * 48-bit Unix timestamp in milliseconds,
                 * 12-bit rand_a,
                 * 62-bit rand_b.
                 */
                $unixTsMs = (int)floor(microtime(true) * 1000);

                if ($unixTsMs < 0 || $unixTsMs > 0xffffffffffff) {
                    throw new InvalidArgumentException(
                        'UUID v7 timestamp exceeds the 48-bit range'
                    );
                }
                $timeLow = ($unixTsMs >> 16) & 0xffffffff;
                $timeMid = $unixTsMs & 0xffff;
                $timeHi = Random::int(0, 0x0fff);

                $randB = bin2hex(Random::bytes(8));
                $clockSeq = hexdec(substr($randB, 0, 4)) & 0x3fff;

                $clockSeqHi = ($clockSeq >> 8) & 0x3f;
                $clockSeqLow = $clockSeq & 0xff;
                $node = hexdec(substr($randB, 4, 12))
                    & 0xffffffffffff;
            }
        } elseif ($type === self::UUID_TYPE_MD5 || $type === self::UUID_TYPE_SHA1) {
            if ($version !== self::UUID_VERSION_3
                && $version !== self::UUID_VERSION_5
            ) {
                throw new InvalidArgumentException(
                    'MD5/SHA-1 generation is only valid for UUID v3/v5'
                );
            }

            $expectedLength = $type === self::UUID_TYPE_MD5 ? 32 : 40;

            if ($hash === null) {
                $hash = $type === self::UUID_TYPE_MD5
                    ? md5(Random::bytes(16))
                    : sha1(Random::bytes(16));
            }

            if (strlen($hash) !== $expectedLength
                || !Filter::hex($hash)
            ) {
                throw new InvalidArgumentException(
                    $type === self::UUID_TYPE_MD5
                        ? 'Invalid MD5 hash for UUID v3'
                        : 'Invalid SHA-1 hash for UUID v5'
                );
            }
            $random = $hash;
        } elseif ($type === self::UUID_TYPE_TIME) {
            if ($version === self::UUID_VERSION_2) {
                throw new InvalidArgumentException(
                    'UUID v2 DCE Security semantics are outside RFC 9562 scope'
                );
            }

            $timestamp = (int)(microtime(true) * 10000000)
                + 0x01B21DD213814000;

            $timeLow = $timestamp & 0xffffffff;
            $timeMid = ($timestamp >> 32) & 0xffff;
            $timeHi = ($timestamp >> 48) & 0x0fff;

            $clockSeqHi = Random::int(0, 0xffff) & 0x3f;
            $clockSeqLow = Random::int(0, 0xff);

            if ($node === null) {
                $node = Random::int(0, 0xffffffffffff)
                    | 0x010000000000;
            }
            $node &= 0xffffffffffff;
        }
        $random ??= bin2hex(Random::bytes(16));
        $timeLow ??= hexdec(substr($random, 0, 8)) & 0xffffffff;
        $timeMid ??= hexdec(substr($random, 8, 4)) & 0xffff;
        $timeHi ??= hexdec(substr($random, 12, 4)) & 0x0fff;
        $clockSeqHi ??= hexdec(substr($random, 16, 2)) & 0x3f;
        $clockSeqLow ??= hexdec(substr($random, 18, 2)) & 0xff;
        $node ??= hexdec(substr($random, 20, 12)) & 0xffffffffffff;

        $version = match ($version) {
            self::UUID_VERSION_1 => 0x1000,
            self::UUID_VERSION_2 => 0x2000,
            self::UUID_VERSION_3 => 0x3000,
            self::UUID_VERSION_4 => 0x4000,
            self::UUID_VERSION_5 => 0x5000,
            self::UUID_VERSION_6 => 0x6000,
            self::UUID_VERSION_7 => 0x7000,
            self::UUID_VERSION_8 => 0x8000,
        };

        $timeHi |= $version;
        $clockSeqHi |= self::UUID_VARIANTS[$variant];

        return sprintf(
            '%08x-%04x-%04x-%02x%02x-%012x',
            $timeLow,
            $timeMid,
            $timeHi,
            $clockSeqHi,
            $clockSeqLow,
            $node
        );
    }

    /**
     * Generate a version 1 (random) UUID.
     *
     * @param int $variant
     * @return string
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1.4
     */
    public function v1(int $variant = self::UUID_VARIANT_DCE): string
    {
        return $this->generate(1, $variant, self::UUID_TYPE_TIME);
    }

    /**
     * Generate a version 2 (DCE Security) UUID.
     *
     * @return string
     * @link https://tools.ietf.org/html/rfc4122#section-4.2
     */
    public function v2(): string
    {
        throw new InvalidArgumentException(
            'UUID v2 DCE Security semantics are outside RFC 9562 scope'
        );
    }

    /**
     * Generate a version 3 (MD5) UUID.
     *
     * @param string $namespace
     * @param string $name
     * @return string
     * @link https://tools.ietf.org/html/rfc4122#section-4.3
     */
    public function v3(
        string $namespace = self::NAMESPACE_DNS,
        string $name = ''
    ): string {
        $hash = $this->calculateNamespaceAndName($namespace, $name, self::UUID_TYPE_MD5);
        return $this->generate(3, self::UUID_VARIANT_RFC4122, self::UUID_TYPE_MD5, $hash);
    }

    /**
     * Generate a version 4 (random) UUID.
     *
     * @return string UUID v4
     */
    public function v4(): string
    {
        /**
         * Generate UUID v4
         */
        return $this->generate(4, self::UUID_VARIANT_RFC4122, self::UUID_TYPE_RANDOM);
    }

    /**
     * Generate a version 5 (SHA-1) UUID.
     *
     * @param string $namespace see UUID::NAMESPACE_* constants
     * @param string $name name to calculate
     * @return string UUID v5
     */
    public function v5(
        string $namespace = self::NAMESPACE_DNS,
        string $name = ''
    ): string {
        $hash = $this->calculateNamespaceAndName($namespace, $name, self::UUID_TYPE_SHA1);
        return $this->generate(5, self::UUID_VARIANT_RFC4122, self::UUID_TYPE_SHA1, $hash);
    }

    /**
     * Generate an RFC 9562 UUID version 6 (reordered Gregorian time-based).
     *
     * UUIDv6 stores the 60-bit UUIDv1 timestamp in most-significant-first
     * order, followed by the RFC 9562 version and variant fields.
     *
     * @param int|null $node Optional 48-bit node value. When omitted, a
     *                      cryptographically random node is generated.
     * @return string UUID v6
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.6
     */
    public function v6(?int $node = null): string
    {
        return $this->generate(6, self::UUID_VARIANT_RFC4122, null, null, $node);
    }

    /**
     * Generate an RFC 9562 UUID version 7 (Unix time-based).
     *
     * The first 48 bits contain Unix Epoch milliseconds. The remaining 74
     * variable bits are filled with fresh random data.
     *
     * @return string UUID v7
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.7
     */
    public function v7(): string
    {
        return $this->generate(7, self::UUID_VARIANT_RFC4122);
    }

    /**
     * Generate an RFC 9562 UUID version 8 (custom UUID).
     *
     * This implementation uses all 122 implementation-defined bits as
     * random/custom data. Pass 32 hexadecimal characters to define the
     * complete 128-bit payload; the required version and variant bits are
     * overwritten by the generator.
     *
     * @param string|null $custom Optional 128-bit hexadecimal custom payload.
     * @return string UUID v8
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.8
     */
    public function v8(?string $custom = null): string
    {
        return $this->generate(
            self::UUID_VERSION_8,
            self::UUID_VARIANT_RFC4122,
            self::UUID_TYPE_RANDOM,
            null,
            null,
            $custom
        );
    }

    /**
     * @inheritdoc
     * Default string return uuid v4
     */
    public function __toString(): string
    {
        return $this->v4();
    }
}
