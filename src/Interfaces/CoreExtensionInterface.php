<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces;

interface CoreExtensionInterface extends ExtensionInterface
{
    /**
     * Get the priority of the core extension
     * The higher the priority, the earlier it will be loaded
     * @return int
     */
    public function getPriority() : int;

    /**
     * Check if core extension should be active
     *
     * @return bool
     */
    public function shouldBeActive(): bool;
}
