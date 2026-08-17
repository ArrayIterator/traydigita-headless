<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Utils;

use function in_array;
use function is_bool;
use function is_float;
use function is_integer;
use function is_string;
use function strtolower;
use function trim;

class Is
{
    public const TRUE = [
        '1' => true,
        'yes' => true,
        'on' => true,
        'true' => true,
        'y' => true,
        'enable' => true,
        'enabled' => true
    ];

    public const FALSE = [
        '' => false,
        '0' => false,
        '0.0' => false,
        'off' => false,
        'none' => false,
        'not' => false,
        'no' => false,
        'n' => false,
        'false' => false,
        'disable' => false,
        'disabled' => false
    ];

    /**
     * Check if an option value is true.
     *
     * @param mixed $data
     * @param bool $default
     * @param bool $use_null_default
     * @return bool
     */
    public static function true(mixed $data, bool $default = false, bool $use_null_default = false): bool
    {
        if (is_bool($data)) {
            return $data;
        }
        if ($use_null_default && $data === null) { // exception to null
            return $default;
        }
        if ($data === 1 || $data === 1.0) {
            return true;
        }
        if (!$data) {
            return false;
        }
        if (is_float($data)) {
            return $data < 2 && $data >= 1;
        }
        if (is_string($data)) {
            if (isset(self::TRUE[$data])) {
                return true;
            }
            if (isset(self::FALSE[$data])) {
                return false;
            }
            $data = strtolower(trim($data));
            return self::TRUE[$data] ?? (self::FALSE[$data] ?? $default);
        }
        return $default;
    }

    /**
     * Check if an option value is false.
     * @param mixed $data
     * @param bool $default
     * @return bool
     */
    public static function false(mixed $data, bool $default = false): bool
    {
        if (is_bool($data)) {
            return $data;
        }
        if (is_string($data)) {
            $data = strtolower(trim($data));
            return in_array($data, ['0', 'no', 'off', 'none', 'false', 'disable', 'disabled'])
                || ((
                    !in_array(
                        $data,
                        ['1', 'yes', 'on', 'true', 'y', 'enable', 'enabled']
                    ) && $default));
        }
        if (is_integer($data)) {
            return $data === 0;
        }
        if (is_float($data)) {
            return $data < 1 && $data >= 0;
        }
        return $default;
    }

    /**
     * Check if the data is a valid hex color.
     *
     * @param mixed $data
     * @return bool
     */
    public static function hex(mixed $data) : bool
    {
        return Filter::hex($data) !== null;
    }
}
