<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use DateTimeInterface;
use JsonSerializable;
use Serializable;

/**
 * @template TSlug of string
 */
interface PluginInfoInterface extends JsonSerializable, Serializable
{
    /**
     * Get the plugin name.
     * @return string The plugin name
     */
    public function getName(): string;

    /**
     * Get the plugin slug.
     * @return TSlug The plugin slug
     */
    public function getSlug(): string;

    /**
     * Get the plugin version.
     * @return string The plugin version
     */
    public function getVersion(): string;

    /**
     * Get the plugin author.
     * @return string|null The plugin author, or null if not available
     */
    public function getAuthor(): ?string;

    /**
     * Get the plugin author URI.
     * @return string|null The plugin author URI, or null if not available
     */
    public function getAuthorProfile(): ?string;

    /**
     * Get the plugin requires PHP version.
     * @return string|null Required PHP version, or null if not available
     */
    public function getRequiresPHP(): ?string;

    /**
     * Get the plugin requires WordPress version.
     * @return string|null Required WordPress version, or null if not available
     */
    public function getRequiresWP(): ?string;

    /**
     * Get the plugin tested WordPress version.
     * @return string|null The tested WordPress version, or null if not available
     */
    public function getTestedVersion(): ?string;

    /**
     * Get the plugin requires plugins.
     * @return array<string>|null The required plugins, or null if not available
     */
    public function getRequiresPlugins(): ?array;

    /**
     * Get the plugin ratings.
     * @return PluginRatingInterface|null The plugin ratings, or null if not available
     */
    public function getRatings(): ?PluginRatingInterface;

    /**
     * Get the plugin support URL.
     * @return string|null The plugin support URL, or null if not available
     */
    public function getSupportUrl(): ?string;

    /**
     * Get the plugin support threads count.
     * @return int|null The plugin support threads count, or null if not available
     */
    public function getSupportThreads(): ?int;

    /**
     * Get the plugin support threads resolved count.
     * @return int|null The plugin support threads resolved count, or null if not available
     */
    public function getSupportThreadsResolved(): ?int;

    /**
     * Get the plugin contributors.
     * @return PluginContributorsInterface<string, PluginContributorInterface>|null
     */
    public function getContributors(): ?PluginContributorsInterface;

    /**
     * Get the plugin homepage URL.
     * @return string|null The plugin homepage URL, or null if not available
     */
    public function getHomepage(): ?string;

    /**
     * Get the plugin download URL.
     * @return string|null The plugin download URL, or null if not available
     */
    public function getDownloadLink(): ?string;

    /**
     * Get the plugin active installations count.
     * @return int|null The plugin active installations count, or null if not available
     */
    public function getActiveInstalls(): ?int;

    /**
     * Get the plugin last updated date.
     * @return DateTimeInterface|null The plugin last updated date, or null if not available
     */
    public function getLastUpdated(): ?DateTimeInterface;

    /**
     * Get the plugin added date.
     * @return DateTimeInterface|null The plugin added date, or null if not available
     */
    public function getAdded(): ?DateTimeInterface;

    /**
     * Get the plugin upgrade notice.
     * @return ?array<string> The plugin upgrade notice, or null if not available
     */
    public function getUpgradeNotice(): ?array;

    /**
     * Get the plugin screenshots.
     * @return PluginScreenShotInterface[]|null The plugin screenshots, or null if not available
     */
    public function getScreenshots(): ?array;

    /**
     * Get the plugin tags.
     * @return array<string, string>|null The plugin tags, or null if not available
     */
    public function getTags(): ?array;

    /**
     * Get the plugin versions.
     * @return array<string, string>|null The plugin versions, or null if not available
     * The key is version number, and the value is the download link for that version.
     */
    public function getVersions(): ?array;

    /**
     * Get the plugin business model.
     * @return string|null|false The plugin business model, or null if not available, or false if not applicable
     */
    public function getBusinessModel(): string|null|false;

    /**
     * Get the plugin repository URL.
     * @return string|null The plugin repository URL, or null if not available
     */
    public function getRepositoryUrl(): ?string;

    /**
     * Get the plugin commercial support URL.
     * @return string|null The plugin commercial support URL, or null if not available
     */
    public function getCommercialSupportUrl(): ?string;

    /**
     * Get the plugin donate link.
     * @return string|null The plugin donate link, or null if not available
     */
    public function getDonateLink(): ?string;

    /**
     * Get the plugin banners.
     * @return array<string, string>|null The plugin banners, or null if not available
     * The key is banner type (e.g. "low", "high"), and the value is the banner URL.
     */
    public function getBanners(): ?array;

    /**
     * Get the plugin preview link.
     * @return string|null The plugin preview link, or null if not available
     */
    public function getPreviewLink(): ?string;

    /**
     * Get the plugin sections.
     * @return PluginSectionsInterface|null The plugin sections, or null if not available
     */
    public function getSections() : ?PluginSectionsInterface;

    /**
     * @return array{
     *     "name": string,
     *     "slug": string,
     *     "version": string,
     *     "author": string|null,
     *     "author_profile": string|null,
     *     "contributors": ?PluginContributorsInterface,
     *     "requires": string|null,
     *     "tested": string|null,
     *     "requires_php": string|null,
     *     "requires_plugins": ?array<string>,
     *     "rating": int|null,
     *     "ratings": ?PluginRatingInterface,
     *     "num_ratings": int|null,
     *     "support_url": string|null,
     *     "support_threads": int|null,
     *     "support_threads_resolved": int|null,
     *     "active_installs": int|null,
     *     "last_updated": string|null,
     *     "added": string|null,
     *     "screenshots": ?array<PluginScreenShotInterface>,
     *     "tags": ?array<string, string>,
     *     "sections": ?PluginSectionsInterface,
     *     "download_link": string|null,
     *     "upgrade_notice": ?array<string>,
     *     "versions": ?array<string, string>,
     *     "business_model": string|null|false,
     *     "repository_url": string|null,
     *     "commercial_support_url": string|null,
     *     "donate_link": string|null,
     *     "banners": ?array<string, string>,
     *     "preview_link": string|null
     * }
     */
    public function jsonSerialize(): array;
}
