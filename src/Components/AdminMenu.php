<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractAdminPage;
use function apply_filters;
use function get_class;
use function is_string;
use function sprintf;
use function strtolower;

class AdminMenu
{
    /**
     * @var string The slug for the admin page
     */
    public const SLUG = 'traydigita-headless';

    /**
     * @var string The capability required to access the admin page
     */
    public const CAPABILITY = 'manage_options';

    /**
     * @var string The icon for the admin page
     */
    public const ICON = 'dashicons-superhero';

    /**
     * @var string $hook The hook for the admin page
     */
    private string $hook;

    /**
     * @var string $pageTitle The page title for the admin page
     */
    private string $pageTitle;

    /**
     * @var string $menuTitle The menu title for the admin page
     */
    private string $menuTitle;

    /**
     * @template T of AbstractAdminPage
     * @var array<lowercase-string<class-string<T>, T>
     */
    private array $submenus;

    /**
     * @var string $slug The slug for the admin page
     */
    private string $slug;

    /**
     * @var string $capability The capability required to access the admin page
     */
    private string $capability;

    /**
     * AdminMenuPage constructor.
     * @param Container $container
     */
    public function __construct(public readonly Container $container)
    {
    }

    /**
     * Get Slug for the admin page
     * @return string
     */
    public function getSlug(): string
    {
        return $this->slug ??= self::SLUG;
    }

    /**
     * @return string
     */
    public function getCapability(): string
    {
        return $this->capability ??= self::CAPABILITY;
    }

    public function getHook(): string
    {
        return $this->hook ??= '';
    }

    public function getPageTitle(): string
    {
        $this->pageTitle ??= __('TrayDigita Headless', 'traydigita');
        $title = apply_filters('traydigita:components:admin_menu_page_title', $this->pageTitle);
        if (!is_string($title) || empty($title)) {
            $title = $this->pageTitle;
        }
        return $title;
    }

    public function getMenuTitle(): string
    {
        $this->menuTitle ??= __('TrayDigita', 'traydigita');
        $tile = apply_filters('traydigita:components:admin_menu_menu_title', $this->menuTitle);
        if (!is_string($tile) || empty($tile)) {
            $tile = $this->menuTitle;
        }
        return $tile;
    }

    public function getIcon(): string
    {
        $default = sprintf(
            'data:image/svg+xml;base64,%s',
            $this->container->traydigita->svgIcon(['padding' => 20], true)
        );
        $icon = apply_filters('traydigita:components:admin_menu_icon', $default);
        if (!is_string($icon) || empty($icon)) {
            $icon = $default;
        }
        return $icon;
    }

    public function removeSubmenu(string|AbstractAdminPage $className): void
    {
        if ($className instanceof AbstractAdminPage) {
            $className = get_class($className);
        }
        $className = strtolower(ltrim($className, '\\'));
        unset($this->submenus[$className]);
    }

    public function addSubmenu(AbstractAdminPage $page): void
    {
        $key = strtolower(get_class($page));
        $this->submenus[$key] = $page;
    }

    public function dispatchHook(): void
    {
        if (isset($this->hook)) {
            return;
        }
        if (!did_action('admin_menu') && !doing_action('admin_menu')) {
            return;
        }
        $this->hook = add_menu_page(
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getSlug(),
            [$this, 'renderAdminPage'],
            $this->getIcon(),
            100
        );
        foreach (($this->submenus ?? []) as $submenu) {
            add_submenu_page(
                self::SLUG,
                $submenu->getPageTitle(),
                $submenu->getMenuTitle(),
                $submenu->getCapability(),
                $submenu->getSlug(),
                $submenu
            );
        }
    }

    public function renderAdminPage(): void
    {
        if (empty($this->hook) || !doing_action($this->hook)) {
            return;
        }
        // todo: render the admin page content here
    }
}
