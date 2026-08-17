<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Abstracts;

use TrayDigita\WP\Headless\Resource\Components\AdminMenu;
use function add_action;
use function add_submenu_page;
use function did_action;
use function doing_action;
use function get_class;
use function remove_action;
use function remove_submenu_page;
use function str_replace;
use function strtolower;
use function wp_localize_script;

abstract class AbstractAdminPage
{
    public const LOCALIZE_KEY = '___TRAYDIGITA_ADMIN_PAGE___';

    /**
     * @var string $slug The slug for the admin page
     */
    protected string $slug;

    /**
     * @var string $capability The capability required to access the admin page
     */
    protected string $capability;

    /**
     * @var string $pageTitle The page title for the admin page
     */
    protected string $pageTitle;

    /**
     * @var string $menuTitle
     */
    protected string $menuTitle;

    /**
     * @var ?string $hookName The Menu title
     */
    private ?string $hookName;

    /**
     * @var bool $inPageHook indicate that current is in hook
     */
    private bool $inPageHook = false;

    /**
     * @var bool $adminEnqueued Indicated already enqueue
     */
    private bool $adminEnqueued = false;

    /**
     * AbstractAdminPage constructor.
     *
     * @param AdminMenu $adminMenu The admin menu instance
     */
    final public function __construct(public readonly AdminMenu $adminMenu)
    {
    }

    /**
     * @param AdminMenu $adminMenu
     * @return void
     */
    final public function deregister(AdminMenu $adminMenu): void
    {
        $hookName = $this->getHookName();
        if (!$hookName) {
            return;
        }
        if (!doing_action('admin_menu') && !did_action('admin_menu')) {
            return;
        }
        remove_submenu_page($adminMenu->getSlug(), $hookName);
        remove_action("load-{$hookName}", [$this, 'pageHook']);
        $this->hookName = null;
    }

    /**
     * Do register admin menu
     *
     * @param AdminMenu $adminMenu
     * @return string|null
     */
    final public function register(AdminMenu $adminMenu): ?string
    {
        $hookName = $this->getHookName();
        if ($hookName) {
            return $hookName;
        }
        if (!doing_action('admin_menu') && !did_action('admin_menu')) {
            return null;
        }
        $this->hookName = add_submenu_page(
            $adminMenu->getSlug(),
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $this->getSlug(),
            [$this, 'render']
        ) ?: null;
        if ($this->hookName) {
            $pageHook = $this->hookName;
            add_action("load-$pageHook", [$this, 'pageHook']);
        }
        return $this->hookName;
    }

    /**
     * Hook callback on page load
     * @return void
     */
    final public function pageHook(): void
    {
        $hookName = $this->getHookName();
        if (!$hookName) {
            return;
        }
        if (!doing_action("load-$hookName")) {
            return;
        }
        $this->inPageHook = true;
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueueScriptHook']);
        $this->dispatch();
    }

    final public function adminEnqueueScriptHook(): void
    {
        if (!$this->inPageHook || $this->adminEnqueued) {
            return;
        }
        if (!doing_action('admin_enqueue_scripts')) {
            return;
        }
        $this->adminEnqueued = true;
        $localize = $this->adminMenu->createLocalizeData($this);
        unset($localize['translations']);
        wp_localize_script(
            $this->adminMenu->container->adminScriptHandle,
            self::LOCALIZE_KEY,
            $localize
        );
        $this->adminQueueScript();
    }

    final public function getHookName(): ?string
    {
        return $this->hookName ?? null;
    }

    /**
     * Check if in current hook
     * @return bool
     */
    final public function isInPageHook(): bool
    {
        return $this->inPageHook;
    }

    /**
     * @return bool
     */
    final public function isAdminEnqueued(): bool
    {
        return $this->isInPageHook() && $this->adminEnqueued;
    }

    /**
     * Get the slug for the admin page
     */
    public function getSlug(): string
    {
        return $this->slug ??= strtolower(str_replace('\\', '-', get_class($this)));
    }

    /**
     * Get the capability required to access the admin page
     */
    public function getCapability(): string
    {
        return $this->capability ?? 'manage_options';
    }

    /**
     * Get the page title for the admin page
     */
    public function getPageTitle(): string
    {
        if (isset($this->pageTitle)) {
            return $this->pageTitle;
        }
        $class = get_class($this);
        // take last part of the class name after the last backslash
        $class = substr($class, strrpos($class, '\\') + 1);
        // convert camel case to space separated words
        $class = preg_replace('/(?<!^)([A-Z])/', ' $1', $class);
        return $this->pageTitle = $class;
    }

    /**
     * Get the menu title for the admin page
     */
    public function getMenuTitle(): string
    {
        if (isset($this->menuTitle)) {
            return $this->menuTitle;
        }
        $class = get_class($this);
        // take last part of the class name after the last backslash
        $class = substr($class, strrpos($class, '\\') + 1);
        // convert camel case to space separated words
        $class = preg_replace('/(?<!^)([A-Z])/', ' $1', $class);
        return $this->menuTitle = $class;
    }

    /**
     * Invoke the render method when the admin page is accessed
     */
    final public function render(): void
    {
        $this->adminMenu->render($this);
    }

    /**
     * Get the localized data for the admin page
     */
    public function getTranslations(): array
    {
        return [];
    }

    /**
     * @abstract
     */
    protected function adminQueueScript()
    {
        // skip override
    }

    /**
     * @abstract
     */
    abstract protected function dispatch();

    /**
     * Get the icon for the admin page
     */
    abstract public function getTsxURL(): string;
}
