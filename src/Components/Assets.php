<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Interfaces\Hooks\HookInitInterface;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function apply_filters;
use function array_any;
use function did_action;
use function doing_action;
use function file_exists;
use function is_array;
use function is_int;
use function is_string;
use function json_decode;
use function ltrim;
use function sprintf;
use function str_ends_with;
use function strlen;
use function substr;
use function trailingslashit;
use function trim;
use function wp_register_script_module;
use function wp_register_style;
use function wp_set_script_module_translations;

class Assets implements HookInitInterface
{
    /**
     * @var string
     */
    public const DISTRIBUTION_PATH = 'dist';

    /**
     * @var string
     */
    public const MANIFEST_PATH = 'manifest.json';

    /**
     * @var array<string, string>
     */
    protected array $manifest;

    /**
     * @var string
     */
    protected string $manifestFile;

    /**
     * @var bool $hookInit
     */
    private bool $hookInit = false;

    /**
     * @inheritdoc
     */
    public function initHook() : void
    {
        if ($this->hookInit) {
            return;
        }
        if (!doing_action('init') && !did_action('init')) {
            return;
        }
        $this->hookInit = true;
        $handle = $this->container->adminScriptHandle;
        $js = $this->getJsManifest($handle);
        $css = $this->getCssManifest($handle);
        if ($js) {
            wp_register_script_module(
                $handle,
                $js['url'],
                [],
                $this->container->version,
                [
                    'strategy' => 'defer',
                    'in_footer' => true
                ]
            );
            wp_set_script_module_translations($handle, 'traydigita');
        }
        if ($css) {
            wp_register_style(
                $handle,
                $css['url'],
                [],
                $this->container->version,
                'all'
            );
        }
    }

    /**
     * Assets constructor.
     *
     * @param Container $container
     */
    public function __construct(public readonly Container $container)
    {
    }

    /**
     * Get the distribution directory path.
     *
     * @param string $path
     * @return string
     */
    public function getDistributionDir(string $path = ''): string
    {
        $distPath = apply_filters(
            'traydigita:assets:distribution_path',
            self::DISTRIBUTION_PATH,
            $path,
            $this->container
        );
        if ($distPath !== self::DISTRIBUTION_PATH && is_string($distPath) && !empty($distPath)) {
            $distPath = trim($distPath, '/');
        }
        $distDir = sprintf(
            '%s%s',
            $this->container->pluginDir,
            $distPath
        );
        $originalDistDir = $distDir;
        if ($path === '/' || $path === '') {
            $distDir = trailingslashit($distDir);
        } else {
            $distDir = sprintf('%s%s', trailingslashit($distDir), ltrim($path, '/'));
        }
        $dir = apply_filters('traydigita:assets:distribution_dir', $distDir, $originalDistDir, $path, $this->container);
        if ($dir !== $distDir && is_string($dir) && !empty($dir)) {
            $distDir = $dir;
        }
        return $distDir;
    }

    /**
     * Get the manifest file path.
     *
     * @return string
     */
    public function getManifestFile(): string
    {
        $manifestFile = sprintf('%s%s', $this->getDistributionDir(), self::MANIFEST_PATH);
        $manifest = apply_filters('traydigita:assets:manifest_file', $manifestFile, $this->container);
        if ($manifest !== $manifestFile && is_string($manifest) && !empty($manifest)) {
            $manifestFile = $manifest;
        }
        return $manifestFile;
    }

    /**
     * Get the plugin URL.
     *
     * @param string $path
     * @return string
     */
    public function getPluginUrl(string $path = ''): string
    {
        $pluginUrl = $this->container->pluginUrl;
        $originalUrl = $pluginUrl;
        if ($path === '/' || $path === '') {
            $pluginUrl = trailingslashit($pluginUrl);
        } else {
            $pluginUrl = sprintf('%s%s', trailingslashit($pluginUrl), ltrim($path, '/'));
        }
        $pluginUri = apply_filters('traydigita:assets:plugin_url', $pluginUrl, $originalUrl, $path, $this->container);
        if ($pluginUrl !== $pluginUri && is_string($pluginUri) && !empty($pluginUri)) {
            $pluginUrl = $pluginUri;
        }
        return $pluginUrl;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getDistributionUrl(string $path = ''): string
    {
        $distPath = apply_filters(
            'traydigita:assets:distribution_path',
            self::DISTRIBUTION_PATH,
            $path,
            $this->container
        );
        if ($distPath !== self::DISTRIBUTION_PATH && is_string($distPath) && !empty($distPath)) {
            $distPath = trim($distPath, '/');
        }
        if ($path === '/' || $path === '') {
            $distUrl = $this->getPluginUrl($distPath);
        } else {
            $distUrl = $this->getPluginUrl(sprintf('%s/%s', $distPath, ltrim($path, '/')));
        }
        $url = apply_filters('traydigita:assets:distribution_url', $distUrl, $distPath, $path, $this->container);
        if (is_string($url) && !empty($url)) {
            $distUrl = $url;
        }
        return $distUrl;
    }

    /**
     * @return array<string, string>
     */
    public function getManifest(): array
    {
        if (!isset($this->manifest) || ($this->manifestFile ?? '') !== ($manifestFile = $this->getManifestFile())) {
            $this->manifestFile = $manifestFile ?? $this->getManifestFile();
            if ($this->manifestFile && file_exists($this->manifestFile)) {
                /**
                 * @var string|false $manifest
                 */
                $manifest = Callback::apply('file_get_contents', $this->manifestFile);
                $manifest = $manifest ? json_decode($manifest, true) : null;
                if (is_array($manifest)) {
                    $this->manifest = $manifest;
                }
            }
            $this->manifest ??= [];
        }
        $manifests = $this->manifest;
        $manifest = apply_filters('traydigita:assets:manifest', $manifests, $this->manifestFile, $this->container);
        if (!is_array($manifest) || array_any(
            $manifest,
            static fn($key, $value) => !is_string($key) || !is_string($value)
        )) {
            return $this->manifest;
        }
        return $manifest;
    }

    /**
     * Get the definitions of a specific asset type (CSS or JS) from the manifest.
     *
     * @param string $name The name of the asset (e.g., "app.js" or "style.css").
     * @param "css"|"js" $type
     * @return ?array{
     *     name: string,
     *     entryName: string,
     *     url : string,
     *     file: string,
     *     path: string,
     *     size: int,
     *     files: array{
     *         file: string,
     *         path: string,
     *         url: string,
     *         size: int,
     *     }[]
     * }
     */
    private function getDefinitionsOf(string $name, string $type): ?array
    {
        $manifest = $this->getManifest();
        if (!isset($manifest[$name]) && str_ends_with($name, ".$type")) {
            // normalize name by removing the extension if it ends with ".$type"
            $name = substr($name, 0, -strlen(".$type"));
        }
        if (!is_array($manifest[$name] ?? null)) {
            return null;
        }
        if (!is_array($manifest[$name][$type] ?? null)) {
            return null;
        }
        $css = $manifest[$name][$type];
        $name = $css['name'] ?? null;
        $entryName = $css['entryName'] ?? null;
        $name = $name ?? $entryName;
        $file = $css['file'] ?? null;
        $size = $css['size'] ?? 0;
        $files = $css['files'] ?? [];
        $size = is_int($size) ? $size : 0;
        if (!is_string($name) || empty($name) || !is_string($file) || !str_ends_with($file, ".$type")) {
            return null;
        }
        $path = $file;
        foreach ($files as $key => $value) {
            if (!is_array($value) || !isset($value['file']) || !is_string($value['file'])) {
                unset($files[$key]);
                continue;
            }
            $dep = $value['file'];
            $value['file'] = $this->getDistributionDir($dep);
            $value['url'] = $this->getDistributionUrl($dep);
            $value['size'] ??= 0;
            $files[$key] = [
                'file' => $value['file'],
                'path' => $dep,
                'url' => $value['url'],
                'size' => is_int($value['size']) ? $value['size'] : 0,
            ];
        }
        $url = $this->getDistributionUrl($path);
        $file = $this->getDistributionDir($path);
        $result = [
            'name' => $name,
            'entryName' => $entryName,
            'url' => $url,
            'path' => $path,
            'file' => $file,
            'size' => $size,
            'files' => $files,
        ];
        $applied = apply_filters('traydigita:assets:manifest:definition', $result, $name, $type, $this->container);
        if (is_array($applied)
            && isset(
                $applied['name'],
                $applied['entryName'],
                $applied['url'],
                $applied['path'],
                $applied['file'],
                $applied['size'],
                $applied['files']
            )
        ) {
            $result = $applied;
        }
        return $result;
    }

    /**
     * Get the CSS definition of a specific asset from the manifest.
     *
     * @param string $name The name of the asset (e.g., "app.js" or "style.css").
     * @return ?array{
     *      name: string,
     *      entryName: string,
     *      url : string,
     *      file: string,
     *      path: string,
     *      size: int,
     *      files: array{
     *          file: string,
     *          path: string,
     *          url: string,
     *          size: int,
     *      }[]
     *  }
     */
    public function getCssManifest(string $name): ?array
    {
        return $this->getDefinitionsOf($name, 'css');
    }

    /**
     * Get the JS definition of a specific asset from the manifest.
     *
     * @param string $name The name of the asset (e.g., "app.js" or "style.css").
     * @return ?array{
     *      name: string,
     *      entryName: string,
     *      url : string,
     *      file: string,
     *      path: string,
     *      size: int,
     *      files: array{
     *          file: string,
     *          path: string,
     *          url: string,
     *          size: int,
     *      }[]
     *  }
     */
    public function getJsManifest(string $name): ?array
    {
        return $this->getDefinitionsOf($name, 'js');
    }
}
