<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces;

use TrayDigita\WP\Headless\Resource\Enums\PluginType;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginInfoInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginUpdateInterface;

/**
 * @template TSlug of string
 */
interface PluginInterface
{
    /**
     * Indicates whether the plugin is valid or not
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Returns the plugin type
     *
     * @return PluginType
     */
    public function getType() : PluginType;

    /**
     * Returns the plugin slug
     *
     * @return TSlug
     */
    public function getSlug() : string;

    /**
     * Indicates whether the plugin is required or not
     *
     * @return bool
     */
    public function isRequired() : bool;

    /**
     * Get the plugin information
     * @return PluginInfoInterface<TSlug>
     */
    public function getPluginInfo(): PluginInfoInterface;

    /**
     * Sets the plugin update information
     *
     * @param PluginUpdateInterface<TSlug>|null $update
     */
    public function setUpdate(?PluginUpdateInterface $update);

    /**
     * Sets the plugin information
     *
     * @param PluginInfoInterface<TSlug> $info
     */
    public function setPluginInfo(PluginInfoInterface $info);

    /**
     * @return PluginUpdateInterface<TSlug>|null
     */
    public function getUpdate() : ?PluginUpdateInterface;
}
