<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use JsonSerializable;
use Serializable;

/**
 * @template TSlug of string
 */
interface PluginUpdateInterface extends JsonSerializable, Serializable
{
    /**
     * Check if the plugin update is valid.
     *
     * @return bool True if the plugin update is valid, false otherwise
     */
    public function isValid() : bool;

    /**
     * Get the unique identifier for the plugin update.
     *
     * @return string|null The unique identifier for the plugin update, or null if not available
     */
    public function getId() : ?string;

    /**
     * Get the slug of the plugin.
     *
     * @return TSlug The slug of the plugin
     */
    public function getSlug() : string;

    /**
     * Get the plugin name.
     *
     * @return string The name of the plugin
     */
    public function getPlugin() : string;

    /**
     * Get the new version of the plugin.
     *
     * @return string|null The new version of the plugin, or null if not available
     */
    public function getNewVersion() : ?string;

    /**
     * Get the URL to the plugin's page on the WordPress.org plugin repository.
     * @return string
     */
    public function getUrl() : string;

    /**
     * Get the package URL for the plugin update.
     *
     * @return string|null The package URL, or null if not available
     */
    public function getPackage() : ?string;

    /**
     * Get the plugin icons
     *
     * @return array<string, string> An associative array of icon sizes and their corresponding URLs
     */
    public function getIcons() : array;

    /**
     * Get the plugin banners
     *
     * @return array<string, string> An associative array of banner sizes and their corresponding URLs
     */
    public function getBanners() : array;

    /**
     * Get the plugin banners for right-to-left languages
     *
     * @return array<string, string> An associative array of banner sizes and their corresponding URLs for RTL languages
     */
    public function getBannersRtl() : array;

    /**
     * Get the plugin requires PHP version.
     * @return string|null Required PHP version, or null if not available
     */
    public function getRequiresPHP() : ?string;

    /**
     * Get the plugin requires WordPress version.
     * @return string|null Required WordPress version, or null if not available
     */
    public function getRequiresWP() : ?string;

    /**
     * Get the plugin tested WordPress version.
     * @return string|null The tested WordPress version, or null if not available
     */
    public function getTestedVersion() : ?string;

    /**
     * Get the required plugins.
     * @return array<string> A list of required plugin slugs
     */
    public function getRequiredPlugins() : array;

    /**
     * Get the plugin compatibility information.
     * @return array<string, mixed> An associative array containing compatibility information
     */
    public function getCompatibility() : array;

    /**
     * Get the plugin upgrade notice.
     * @return string|null The upgrade notice, or null if not available
     */
    public function getUpgradeNotice(): ?string;

    /**
     * @return array{
     *     "id": string|null,
     *     "slug": TSlug,
     *     "plugin": string,
     *     "new_version": string|null,
     *     "url": string,
     *     "package": string|null,
     *     "icons": array<string, string>,
     *     "banners": array<string, string>,
     *     "banners_rtl": array<string, string>,
     *     "requires_php": string|null,
     *     "requires": string|null,
     *     "tested": string|null,
     *     "required_plugins": array<string>,
     *     "compatibility": array<string, mixed>,
     *     "upgrade_notice": string|null
     * }
     */
    public function jsonSerialize(): array;
}
