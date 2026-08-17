<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use JsonSerializable;
use Serializable;

/**
 * @template TUsername of string
 * @template TContributor of PluginContributorInterface
 */
interface PluginContributorsInterface extends JsonSerializable, Serializable
{

    /**
     * Check if a contributor exists in the collection by username.
     *
     * @param TUsername $username
     * @return bool True if the contributor exists, false otherwise.
     */
    public function has(string $username): bool;

    /**
     * Get a contributor by username.
     *
     * @param TUsername $username
     * @return TContributor|null The contributor object, or null if not found.
     */
    public function get(string $username) : ?PluginContributorInterface;

    /**
     * @param TContributor $contributor
     */
    public function add(PluginContributorInterface $contributor);

    /**
     * Remove a contributor from the collection.
     *
     * @param TUsername|TContributor $contributor string is username of the contributor,
     * or the contributor object itself.
     * @return TContributor|null The removed contributor object, or null if not found.
     */
    public function remove(string|PluginContributorInterface $contributor) : ?PluginContributorInterface;

    /**
     * Get all contributors in the collection.
     *
     * @return array<TUsername, TContributor>
     */
    public function all() : array;

    /**
     * Implement the JsonSerializable interface to allow the collection to be serialized to JSON.
     *
     * @return array<TUsername, TContributor>
     */
    public function jsonSerialize(): array;
}
