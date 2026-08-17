<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Utils;

use WP_Post;
use function _wp_get_attachment_relative_path;
use function absint;
use function apply_filters;
use function is_array;
use function is_int;
use function is_numeric;
use function is_ssl;
use function is_string;
use function min;
use function parse_url;
use function preg_match;
use function round;
use function rtrim;
use function set_url_scheme;
use function sprintf;
use function str_replace;
use function strlen;
use function strpos;
use function trailingslashit;
use function wp_basename;
use function wp_calculate_image_sizes;
use function wp_get_attachment_image_src;
use function wp_get_attachment_metadata;
use function wp_get_upload_dir;

class ImageUtil
{
    /**
     * Calculate Image Ratio
     *
     * @param int $width
     * @param int $height
     * @param bool $as_int
     * @return ?array{width: int|float, height: int|float}
     */
    public static function calculateImageRatio(int $width, int $height, bool $as_int = true): ?array
    {
        if ($width <= 0 || $height <= 0) {
            return null;
        }
        $gcd = function (int $a, int $b) use (&$gcd): int {
            return $b === 0 ? $a : $gcd($b, $a % $b);
        };
        $divisor = $gcd($width, $height);
        // safe
        if ($divisor <= 0) {
            return null;
        }
        $width = $width / $divisor;
        $height = $height / $divisor;
        if ($as_int) {
            $width = (int)$width;
            $height = (int)$height;
        }
        return [
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Calculate new dimensions based on a target width while maintaining aspect ratio
     *
     * @param int $ratioWidth Lebar rasio (misal: 16)
     * @param int $ratioHeight Tinggi rasio (misal: 9)
     * @param int $sourceWidth Lebar gambar asli (untuk validasi/perbandingan)
     * @param int $targetWidth Lebar target yang diinginkan
     * @return array{width: int, height: int}|null
     */
    public static function resizeRatioWidth(
        int $ratioWidth,
        int $ratioHeight,
        int $sourceWidth,
        int $targetWidth
    ): ?array {
        if ($ratioWidth <= 0 || $ratioHeight <= 0 || $sourceWidth <= 0 || $targetWidth <= 0) {
            return null;
        }

        // Formula: Height = (TargetWidth * RatioHeight) / RatioWidth
        $targetHeight = (int)round(($targetWidth * $ratioHeight) / $ratioWidth);

        if ($targetHeight <= 0) {
            return null;
        }

        return [
            'width' => $targetWidth,
            'height' => $targetHeight,
        ];
    }

    /**
     * Resize an image while maintaining its aspect ratio to fit within a target width and height.
     *
     * @param int $width Original width of the image
     * @param int $height Original height of the image
     * @param int $targetWidth Target width to fit the image into
     * @param int $targetHeight Target height to fit the image into
     * @return array{width: int, height: int}|null Returns new dimensions or null if invalid input
     */
    public static function resizeRatio(
        int $width,
        int $height,
        int $targetWidth,
        int $targetHeight
    ): ?array {
        if ($width <= 0 || $height <= 0 || $targetWidth <= 0 || $targetHeight <= 0) {
            return null;
        }
        $scaleW = $targetWidth / $width;
        $scaleH = $targetHeight / $height;
        $scale = min($scaleW, $scaleH);
        $newWidth = (int)round($width * $scale);
        $newHeight = (int)round($height * $scale);
        return [
            'width' => $newWidth,
            'height' => $newHeight,
        ];
    }

    /**
     * Check if two sets of dimensions have the same aspect ratio.
     *
     * @param int|float $sourceWidth
     * @param int|float $sourceHeight
     * @param int|float $targetWidth
     * @param int|float $targetHeight
     * @return bool
     */
    public static function isMatchedRatio(
        $sourceWidth,
        $sourceHeight,
        $targetWidth,
        $targetHeight
    ): bool {
        if (!is_numeric($sourceWidth)
            || !is_numeric($sourceHeight)
            || !is_numeric($targetWidth)
            || !is_numeric($targetHeight)
        ) {
            return false;
        }
        $sourceWidth = (int)$sourceWidth;
        $sourceHeight = (int)$sourceHeight;
        $targetWidth = (int)$targetWidth;
        $targetHeight = (int)$targetHeight;
        if ($sourceWidth === $targetWidth) {
            return $sourceHeight === $targetHeight;
        }
        $source_ratio = self::calculateImageRatio($sourceWidth, $sourceHeight);
        if (!$source_ratio) {
            return false;
        }
        ['width' => $s_w, 'height' => $s_h] = $source_ratio;
        $targetRatio = self::calculateImageRatio($targetWidth, $targetHeight);
        if (!$targetRatio) {
            return false;
        }
        ['width' => $t_w, 'height' => $t_h] = $targetRatio;
        return $s_w === $t_w && $s_h === $t_h;
    }

    /**
     * Check if the given source is a valid image source array and return a standardized format.
     *
     * @param array{
     *       width:int,
     *       height:int,
     *       url?:string,
     *       file?:string,
     *   }|array{
     *       0: string,
     *       1: int,
     *       2: int,
     *   }|mixed $source
     * @return array|null
     */
    public static function checkImageSrc(mixed $source): ?array
    {
        if (!is_array($source)) {
            return null;
        }
        if (is_string($source[0] ?? null) && is_numeric($source[1] ?? null) && is_numeric($source[2] ?? null)) {
            return [
                'url' => $source[0],
                'width' => absint($source[1]),
                'height' => absint($source[2]),
            ];
        }
        $width = $source['width'] ?? null;
        $height = $source['height'] ?? null;
        $url = null;
        if (is_string($source['url'] ?? null)) {
            $url = $source['url'];
        } elseif (is_string($source['file'] ?? null)) {
            $url = $source['file'];
        }
        if (!is_string($url) || !is_numeric($width) || !is_numeric($height)) {
            return null;
        }
        $width = (int)$width;
        $height = (int)$height;
        if ($width <= 0 || $height <= 0) {
            return null;
        }
        return [
            'width' => $width,
            'height' => $height,
            'url' => $url,
        ];
    }

    /**
     * Determine the srcset for a given attachment ID and size.
     *
     * @param $attachment_id
     * @param string $size
     * @return ?array{
     *      image: array{
     *        width:int,
     *        height:int,
     *       url: string,
     *      },
     *      srcset: string,
     *      sizes: string,
     *      srcset_data: array{
     *          url: string,
     *          descriptor: string,
     *          value: int,
     *      },
     *  }
     */
    public static function determineWPSrcset($attachment_id, string $size): ?array
    {
        if ($attachment_id instanceof WP_Post) {
            if (!str_starts_with($attachment_id->post_mime_type, 'image/')) {
                return null;
            }
            $attachment_id = $attachment_id->ID;
        }
        if (!is_numeric($attachment_id)) {
            return null;
        }
        $attachment_id = (int)$attachment_id;
        $image = self::checkImageSrc(wp_get_attachment_image_src($attachment_id, $size));
        if (!$image) {
            return null;
        }
        $image_meta = wp_get_attachment_metadata($attachment_id);
        if (!$image_meta) {
            return null;
        }
        if (!is_array($image_meta['sizes'] ?? null)
            || !is_string($image_meta['file'] ?? null)
            || strlen($image_meta['file']) < 4) {
            return null;
        }
        $image_src = $image['url'];
        $image_sizes = $image_meta['sizes'];
        $image_basename = wp_basename($image_meta['file']);
        $mime_type = $image_sizes['thumbnail']['mime-type'] ?? null;
        if ('image/gif' !== $mime_type && isset($image_meta['width']) && isset($image_meta['height'])) {
            $image_sizes[] = [
                'width' => $image_meta['width'],
                'height' => $image_meta['height'],
                'file' => $image_basename,
            ];
        } elseif (str_contains($image_src, $image_meta['file'])) {
            return null;
        }

        $dirname = _wp_get_attachment_relative_path($image_meta['file']);
        if ($dirname) {
            $dirname = trailingslashit($dirname);
        }
        $upload_dir = wp_get_upload_dir();
        $image_baseurl = trailingslashit($upload_dir['baseurl']) . $dirname;
        /*
         * If currently on HTTPS, prefer HTTPS URLs when we know they're supported by the domain
         * (which is to say, when they share the domain name of the current request).
         */
        if (is_ssl() && !str_starts_with($image_baseurl, 'https')) {
            /*
             * Since the `Host:` header might contain a port, it should
             * be compared against the image URL using the same port.
             */
            $parsed = parse_url($image_baseurl);
            $domain = $parsed['host'] ?? '';
            if (isset($parsed['port'])) {
                $domain .= ':' . $parsed['port'];
            }
            if (($_SERVER['HTTP_HOST'] ?? null) === $domain) {
                $image_baseurl = set_url_scheme($image_baseurl, 'https');
            }
        }
        foreach ($image_sizes as $key => $v) {
            if (!is_string($v['file'] ?? null)) {
                unset($image_sizes[$key]);
                continue;
            }
            $url = $image_baseurl . $v['file'];
            $image_url = apply_filters('traydigita_feature_image_size_url', $url, $v, $key, $image_baseurl, $size);
            $url = is_string($image_url) ? $image_url : $url;
            $image_sizes[$key]['file'] = $url;
        }
        return self::determineSrcset($image, $image_sizes);
    }

    /**
     * Determine the srcset for a given source image and an array of images.
     *
     * @template ParamImage of array{
     *      width:int,
     *      height:int,
     *      url?:string,
     *      file?:string,
     *  }
     * @param ParamImage $source
     * @param ParamImage[] $images
     * @param numeric|int|mixed $max_srcset_image_width
     * @return ?array{
     *     image: array{
     *       width:int,
     *       height:int,
     *      url: string,
     *     },
     *     srcset: string,
     *     sizes: string,
     *     srcset_data: array{
     *         url: string,
     *         descriptor: string,
     *         value: int,
     *     },
     * }
     */
    public static function determineSrcset(
        array $source,
        array $images,
        mixed $max_srcset_image_width = null
    ): ?array {
        if (is_numeric($max_srcset_image_width)) {
            $max_srcset_image_width = (int)$max_srcset_image_width;
            if ($max_srcset_image_width <= 0) {
                return null;
            }
        }
        if ($max_srcset_image_width && !is_int($max_srcset_image_width)) {
            return null;
        }

        $source = self::checkImageSrc($source);
        if (!$source) {
            return null;
        }
        /*
         * Images that have been edited in WordPress after being uploaded will
         * contain a unique hash. Look for that hash and use it later to filter
         * out images that are leftovers from previous versions.
         */
        $image_edited = preg_match('/-e[0-9]{13}/', wp_basename($source['url']), $image_edit_hash);
        $size_array = [$source['width'], $source['height']];
        $max_srcset_image_width ??= apply_filters(
            'max_srcset_image_width',
            2048,
            $size_array
        );

        ['width' => $width, 'height' => $height, 'url' => $url] = $source;
        $dirname = _wp_get_attachment_relative_path($url);
        if ($dirname) {
            $dirname = trailingslashit($dirname);
        }
        $sources = [];
        $src_matched = false;
        foreach ($images as $item) {
            $src_set = self::checkImageSrc($item);
            if (!$src_set) {
                continue;
            }
            $is_src = false;
            ['width' => $w, 'height' => $h, 'url' => $u] = $src_set;
            $base = wp_basename($u);
            $base = $dirname . $base;
            $base_full = $base;
            if ($base_full[0] !== '/') {
                $base_full = "/$base_full";
            }
            // If the file name is part of the `src`, we've confirmed a match.
            if (!$src_matched && ($url === $u || str_contains($url, $base_full))) {
                $src_matched = true;
                $is_src = true;
            }
            // Filter out images that are from previous edits.
            if ($image_edited && !strpos($u, $image_edit_hash[0])) {
                continue;
            }
            /*
             * Filters out images that are wider than '$max_srcset_image_width' unless
             * that file is in the 'src' attribute.
             */
            if ($max_srcset_image_width && $w > $max_srcset_image_width && !$is_src) {
                continue;
            }
            if (!self::isMatchedRatio($width, $height, $w, $h)) {
                continue;
            }
            $source = [
                'url' => $u,
                'descriptor' => 'w',
                'value' => $w,
                'width' => $w,
                'height' => $h,
            ];
            // The 'src' image has to be the first in the 'srcset', because of a bug in iOS8. See #35030.
            if ($is_src) {
                $sources = [$w => $source] + $sources;
            } else {
                $sources[$w] = $source;
            }
        }

        // Only return a 'srcset' value if there is more than one source.
        if ($src_matched && count($sources) >= 2) {
            $sizes = wp_calculate_image_sizes($size_array, $url);
            if (!$sizes) {
                $sizes = sprintf('(max-width: %1$dpx) 100vw, %1$dpx', $width);
            }
            $srcset = '';
            foreach ($sources as $s) {
                $srcset .= str_replace(
                    ' ',
                    '%20',
                    $source['url']
                );
                $srcset .= ' ' . $s['value'] . $s['descriptor'] . ', ';
            }
            $srcset = rtrim($srcset, ', ');
            return [
                'image' => $source,
                'srcset' => $srcset,
                'sizes' => $sizes,
                'srcset_data' => $sources
            ];
        }
        return null;
    }
}
