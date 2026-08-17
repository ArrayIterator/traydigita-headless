<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use JsonSerializable;
use Serializable;

interface PluginContributorInterface extends JsonSerializable, Serializable
{
    /**
     * Get the username of the contributor.
     *
     * @return string The username of the contributor.
     */
    public function getUsername(): string;

    /**
     * Get the profile URL of the contributor.
     *
     * @return string The URL to the contributor's profile page.
     */
    public function getProfileURL(): string;

    /**
     * Get the avatar URL of the contributor.
     *
     * @return string The URL to the contributor's avatar image.
     */
    public function getAvatarUrl(): string;

    /**
     * Get the display name of the contributor.
     *
     * @return string The display name of the contributor.
     */
    public function getDisplayName(): string;

    /**
     * Implement the JsonSerializable interface to allow the contributor to be serialized to JSON.
     *
     * @return array{
     *     profile: string,
     *     avatar: string,
     *     display_name: string
     * }
     */
    public function jsonSerialize(): array;
}
