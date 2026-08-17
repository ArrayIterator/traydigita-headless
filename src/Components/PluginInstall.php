<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use JsonSerializable;
use TrayDigita\WP\Headless\Resource\Interfaces\PluginInterface;

class PluginInstall implements JsonSerializable
{
    /**
     * @var array<string, PluginInterface<string>> $plugins
     */
    private array $plugins;

    /**
     * @param Container $container
     */
    public function __construct(public readonly Container $container)
    {
    }

    /**
     * Add a plugin to the installation list
     *
     * @param PluginInterface<string> $plugin
     */
    public function add(PluginInterface $plugin): void
    {
        $this->plugins[$plugin->getSlug()] = $plugin;
    }

    /**
     * Remove a plugin from the installation list
     *
     * @param string $slug
     * @return PluginInterface<string>|null
     */
    public function remove(string $slug): ?PluginInterface
    {
        if (!isset($this->plugins)) {
            return null;
        }
        $plugin = $this->plugins[$slug] ?? null;
        unset($this->plugins[$slug]);
        return $plugin;
    }

    /**
     * Get a plugin from the installation list
     *
     * @param string $slug
     * @return PluginInterface<string>|null
     */
    public function get(string $slug): ?PluginInterface
    {
        return $this->plugins[$slug] ?? null;
    }

    /**
     * Get all plugins in the installation list
     *
     * @return array<string, PluginInterface<string>>
     */
    public function all(): array
    {
        return $this->plugins??[];
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array<string, PluginInterface<string>>
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
