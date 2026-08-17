<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Abstracts;

use function get_class;
use function str_replace;
use function strtolower;

abstract class AbstractAdminPage
{
    /**
     * @var string The slug for the admin page
     */
    protected string $slug;

    /**
     * @var string The capability required to access the admin page
     */
    protected string $capability;

    /**
     * Get the page title for the admin page
     */
    abstract public function getPageTitle() : string;

    /**
     * Get the menu title for the admin page
     */
    abstract public function getMenuTitle() : string;

    /**
     * Render the admin page content
     */
    abstract public function render();

    /**
     * Get the slug for the admin page
     */
    public function getSlug() : string
    {
        return $this->slug ??= strtolower(str_replace('\\', '-', get_class($this)));
    }

    public function getCapability() : string
    {
        return $this->capability ?? 'manage_options';
    }

    final public function __invoke(): void
    {
        $this->render();
    }
}
