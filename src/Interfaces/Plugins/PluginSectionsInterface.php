<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use JsonSerializable;
use Serializable;

interface PluginSectionsInterface extends JsonSerializable, Serializable
{
    /**
     * Get the plugin descriptions
     * @return string|null
     */
    public function getDescriptions(): ?string;

    /**
     * Get the plugin installation instructions
     * @return string|null
     */
    public function getInstallation() : ?string;

    /**
     * Get plugin change log
     *
     * @return string|null
     */
    public function getChangeLog(): ?string;

    /**
     * Get plugin FAQ
     *
     * @return string|null
     */
    public function getFAQ(): ?string;

    /**
     * Get plugin reviews
     *
     * @return string|null
     */
    public function getReviews(): ?string;

    /**
     * @return array{
     *     descriptions: string|null,
     *     installation: string|null,
     *     changeLog: string|null,
     *     faq: string|null,
     *     reviews: string|null
     * }
     */
    public function jsonSerialize(): array;
}
