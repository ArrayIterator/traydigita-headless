<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Features\Image;
use function crc32;
use function in_array;
use function is_array;
use function is_ssl;
use function is_string;
use function ltrim;
use function pathinfo;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function strtolower;
use function wp_parse_url;
use const PATHINFO_EXTENSION;

class Photon
{
    public const WP_CDN_LISTS = [
        'i0.wp.com',
//        'i1.wp.com',
//        'i2.wp.com',
//        'i3.wp.com',
    ];

    public const JETPACK_IMAGE_SUPPORTS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'avif',
        'heic'
    ];

    public function __construct(public readonly Site $site)
    {
    }

    public function index(mixed $url): ?array
    {
        if ($url instanceof Image) {
            $url = $url->getFeatureImageUrl();
        }
        if (!is_string($url)) {
            return null;
        }
        foreach (self::WP_CDN_LISTS as $list) {
            /** @noinspection HttpUrlsUsage */
            if (str_starts_with($url, "https://$list/")
                || str_starts_with($url, "http://$list/")
            ) {
                return null;
            }
        }
        $scheme = is_ssl() ? 'https' : 'http';
        if (str_starts_with($url, '//')) {
            $url = "$scheme:$url";
        }
        $host = $this->site->getHostname();
        $parsed = wp_parse_url($url);
        $src_host = $parsed['host'] ?? null;
        $path = $parsed['path'] ?? null;
        if (!$path || $src_host && $src_host !== $host) {
            return null;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (!$ext || !in_array(strtolower($ext), self::JETPACK_IMAGE_SUPPORTS)) {
            return null;
        }
        if (!$src_host) {
            $url = sprintf('%s://%s', $scheme, '/' . ltrim($url, '/'));
        }
        $index = crc32($path) % count(self::WP_CDN_LISTS);
        return [
            'url' => $url,
            'host' => $src_host ?: $host,
            'path' => $path,
            'index' => $index
        ];
    }

    public function urlIndex(int $index): ?string
    {
        $domain = self::WP_CDN_LISTS[$index] ?? null;
        return $domain ? "https://$domain/" : null;
    }

    public function replaceWithIndex(string $url, int $index, ?bool &$found = null): string
    {
        $uri = $this->urlIndex($index);
        $found = false;
        if ($uri) {
            if ($this->satisfied($url)) {
                $found = true;
                return $url;
            }
            return preg_replace_callback(
                '~^(?:https?:)?//([^/]+)(/.*)?$~',
                function ($match) use ($uri, &$found) {
                    if (in_array($match[1], self::WP_CDN_LISTS)) {
                        return $match[0];
                    }
                    $found = true;
                    return $uri . $match[1] . $match[2];
                },
                $url
            );
        }
        return $url;
    }

    public function replace(mixed $url, ?bool &$found = null): string
    {
        if (is_string($url) && $this->satisfied($url)) {
            $found = true;
            return $url;
        }
        $parsed = $this->index($url);
        if (!is_array($parsed)) {
            return $url;
        }
        return $this->replaceWithIndex($parsed['url'], $parsed['index'], $found);
    }

    public function satisfied(mixed $url): bool
    {
        return is_string($url) && preg_match(
            '~^https?://i[0-4].wp.com/.+(jpeg?|png|webp|gif|avif|heic)(?:\?.*)?$~i',
            $url
        );
    }
}
