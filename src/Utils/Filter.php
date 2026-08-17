<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Utils;

use RuntimeException;
use TrayDigita\WP\Headless\Resource\Features\Image;
use WP_Post;
use function array_pop;
use function explode;
use function get_post;
use function implode;
use function is_int;
use function is_numeric;
use function is_string;
use function parse_url;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strlen;
use function strtolower;
use function substr;
use function trim;

class Filter
{
    /**
     * Get post meta value
     *
     * @param mixed|WP_Post|int $post
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function postMeta(mixed $post, string $key, mixed $default = null): mixed
    {
        $post = self::post($post);
        if (!$post) {
            return $default;
        }
        return get_post_meta($post->ID, $key, true) ?: $default;
    }

    /**
     * Retrieve WP_Post object from mixed value
     *
     * @param mixed|WP_Post|int $post
     * @param bool $preventZero
     * @return WP_Post|null
     */
    public static function post(mixed $post, bool $preventZero = true): ?WP_Post
    {
        if ($post instanceof WP_Post) {
            if ($preventZero && $post->ID <= 0) {
                return null;
            }
            return $post;
        }
        if ($post instanceof Image) {
            return $post->getPost();
        }
        if (!is_int($post) && is_numeric($post)) {
            $post = (string)$post;
            if (str_contains($post, '.')) {
                $post = preg_replace('~\.0+$~', '', $post);
            }
            if (str_contains($post, '.')) {
                return null;
            }
            $post = (int)$post;
        }
        if (is_int($post)) {
            if ($post < 0) {
                return null;
            }
            if ($preventZero && $post === 0) {
                return null;
            }
            $post = get_post($post);
        }
        if (!$post instanceof WP_Post) {
            return null;
        }
        if ($preventZero && $post->ID <= 0) {
            return null;
        }
        return $post;
    }

    /**
     * Filter color
     *
     * @param string $color
     * @return string|null
     */
    public static function colorHex(mixed $color): ?string
    {
        if (!is_string($color)) {
            return null;
        }
        $color = strtolower(trim($color));
        if (str_contains($color, 'rgb')
            && preg_match(
                '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})(?:\s*,\s*([0-9.]+))?\s*\)$/',
                $color,
                $matches
            )
        ) {
            $r = (int)$matches[1];
            $g = (int)$matches[2];
            $b = (int)$matches[3];
            $a = isset($matches[4]) ? (float)$matches[4] : 1.0;
            if ($a <= 0) {
                return null;
            }
            if ($r > 255 || $g > 255 || $b > 255) {
                return null;
            }
            return sprintf('#%02x%02x%02x', $r, $g, $b);
        } elseif (str_starts_with($color, '#')) {
            $color = substr($color, 1);
        }
        $length = strlen($color);
        if ($length === 3 && preg_match('/^[0-9a-fA-F]{3}$/', $color)) {
            $color = preg_replace('/([0-9a-fA-F])/', '$1$1', $color);
            return "#$color";
        }

        if ($length === 6 && preg_match('/^[0-9a-fA-F]{6}$/', $color)) {
            return "#$color";
        }
        if ($length === 8 && preg_match('/^[0-9a-fA-F]{8}$/', $color)) {
            $color = substr($color, 0, 6);
            return "#$color";
        }
        return null;
    }

    /**
     * Get relative path from URL
     *
     * @param string $url
     * @return string
     */
    public static function relativePathURL(string $url): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        $segments = explode('/', $path);
        $absSegments = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }
            if ($segment === '..') {
                array_pop($absSegments);
            } else {
                $absSegments[] = $segment;
            }
        }

        $scheme = isset($parts['scheme']) ? $parts['scheme'] . '://' : '';
        $host = $parts['host'] ?? '';

        return $scheme . $host . '/' . implode('/', $absSegments);
    }

    /**
     * Filter hex string
     *
     * @param mixed $hex
     * @return string|null
     */
    public static function hex(mixed $hex): ?string
    {
        if (!is_string($hex)) {
            return null;
        }
        $hex = trim($hex);
        if (!preg_match('/^[0-9a-fA-F]+$/', $hex)) {
            return null;
        }
        return $hex;
    }

    /**
     * Filter number, convert scientific notation to standard notation
     *
     * @param mixed $number
     * @return string|null
     */
    public static function number(mixed $number) : ?string
    {
        if (is_string($number)) {
            $number = trim($number);
        }

        if (!is_numeric($number)) {
            return null;
        }

        // replace E to e
        $number = str_replace('E', 'e', strval($number));
        // Convert a number in scientific notation to standard notation
        if (str_contains($number, 'e')) {
            [$mantissa, $exponent] = explode('e', $number);
            if (($minus = $mantissa[0] === '-') || $mantissa[0] === '+') {
                $mantissa = substr($mantissa, 1);
            }
            if (($isDecimalPoint = $exponent[0] === '-') || $exponent[0] === '+') {
                $exponent = substr($exponent, 1);
            }
            $exponent = (int)$exponent;
            if ($exponent >= PHP_INT_MAX) {
                throw new RuntimeException(
                    'Exponent is too large'
                );
            }
            // check additional exponent
            $additionalExponent = 0;
            if (!$isDecimalPoint && str_contains($mantissa, '.')) {
                $additionalExponent = strlen(explode('.', $mantissa)[0]);
            }
            $exponent = $exponent + $additionalExponent;
            $mantissa = str_replace('.', '', $mantissa);
            if ($isDecimalPoint) {
                // - is decimal point, convert mantissa
                $mantissa = substr(str_repeat('0', $exponent - 1) . $mantissa, 0, $exponent + 1);
                $mantissa = '0.' . $mantissa;
            } else {
                $mantissa = str_pad($mantissa, $exponent, '0');
                if (strlen($mantissa) > $exponent) {
                    $mantissa = substr($mantissa, 0, $exponent+1)
                        . '.'
                        . substr($mantissa, $exponent + 1, strlen($mantissa) - $exponent);
                }
                // trim right padding
                $mantissa = rtrim($mantissa, '.0');
            }

            $number = $minus ? '-' . $mantissa : $mantissa;
        }

        return $number;
    }
}
