<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource;

use TrayDigita\WP\Headless\Resource\Components\Container;
use TrayDigita\WP\Headless\Resource\Utils\Filter;
use function add_action;
use function array_merge;
use function do_action;
use function intval;
use function is_int;
use function sprintf;

/**
 * @mixin Container
 */
final class TrayDigita
{
    /**
     * @var string $pluginBasename The plugin basename
     */
    public readonly string $pluginBasename;

    /**
     * @var bool $initialized Whether the instance has been initialized
     */
    private bool $initialized = false;

    /**
     * TrayDigita constructor.
     * @param Container $container The container instance
     */
    public function __construct(
        public readonly Container $container,
    ) {
        $this->pluginBasename = $this->container->pluginFile;
    }

    /**
     * Generate an SVG icon
     *
     * @param array $attributes The attributes for the SVG element
     * @param bool $asBase64 Whether to return the SVG as a base64-encoded string
     * @return string The generated SVG icon
     */
    public function svgIcon(array $attributes = [], bool $asBase64 = false): string
    {
        // phpcs:disable Generic.Files.LineLength
        $d = 'm16 65c14.1 61.8 62.4 115 95.4 169 138 182 386 233 603 207 305-32.8 622 0.873 903 128 151 71.3 288 182 367 332 4.07-99.7-7.97-199-46.2-292-99.1-289-367-515-673-548-332-13.9-664-5.07-996-10.3-84.4-0.527-169-0.547-253-0.744v14.1zm365 424c-3 487-3.11 973-0.35 1460 138 3.66 277 0.649 415 1.32-0.319-477 1.53-953-0.75-1430-138-2.51-279 4.42-414-31.4zm1103 92.8c71.4 121 147 249 145 395 0.332 186-63.4 400-244 487-164 81.3-352 79.2-531 82.4-2.87 134-1.51 268-0.53 403 271 7.43 562-6.44 794-162 224-139 377-412 320-679-49.4-248-259-433-483-526z';
        $color = $attributes['color'] ?? '#3e5580';
        $color = Filter::colorHex($color);
        $color = $color ?: '#3e5580';
        $style = "--svg-color:$color;";
        if (isset($attributes['style'])) {
            $style = $attributes['style'] . ';' . $style;
        }
        $viewBox = 2000;
        $xyStart = 0;
        // padding percentage of the viewBox size, e.g. 10% of 2000 = 200
        $padding = $attributes['padding'] ?? 0;
        if (is_int($padding)) {
            $percentageViewBox = intval($viewBox * ($padding / 100));
            $viewBox = $viewBox + $percentageViewBox * 2;
            $xyStart = -$percentageViewBox;
        }
        unset($attributes['padding'], $attributes['color']);
        $default = [
            'xmlns' => 'http://www.w3.org/2000/svg',
            'fill' => 'var(--svg-color, currentColor)',
            'style' => $style,
            'viewBox' => sprintf('%1$s %1$s %2$d %2$d', $xyStart, $viewBox),
        ];
        $attributes = array_merge($attributes, $default);
        $attribute = $this->container->attributes->buildAttributes($attributes);
        $svg = <<<HTML
<svg $attribute><path d="$d"/></svg>
HTML;

        if ($asBase64) {
            return sprintf('data:image/svg+xml;base64,%s', base64_encode($svg));
        }

        return $svg;
    }

    /**
     * Initialize the TrayDigita instance
     */
    public function init(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        do_action('traydigita:init', $this);
        add_action('admin_menu', [$this->admin_menu, 'dispatchHook']);
        add_action('rest_api_init', [$this->rest, 'dispatchHook']);
    }

    /**
     * Get the container instance
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    public function __call(string $name, array $arguments)
    {
        return $this->container->$name(...$arguments);
    }

    public function __set(string $name, $value): void
    {
        $this->container->$name = $value;
    }

    public function __get(string $name)
    {
        return $this->container->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->container->$name);
    }
}
