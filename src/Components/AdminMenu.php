<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractAdminPage;
use TrayDigita\WP\Headless\Resource\Components\Dependencies\AdminMenu\MainMenu;
use TrayDigita\WP\Headless\Resource\Exceptions\InvalidOperationException;
use TrayDigita\WP\Headless\Resource\Interfaces\Hooks\HookAdminEnqueueScriptsInterface;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function __;
use function add_action;
use function add_menu_page;
use function apply_filters;
use function array_map;
use function array_search;
use function class_exists;
use function current_action;
use function did_action;
use function do_action;
use function doing_action;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function is_subclass_of;
use function ltrim;
use function remove_action;
use function remove_menu_page;
use function str_starts_with;
use function strtolower;
use function substr;
use function wp_enqueue_script_module;
use function wp_enqueue_style;
use function wp_localize_script;

class AdminMenu implements HookAdminEnqueueScriptsInterface
{
    public const LOCALIZE_KEY = '___TRAYDIGITA_ADMIN_PAGES___';

    /**
     * @var string The slug for the admin page
     */
    public const SLUG = 'traydigita-headless';

    /**
     * @var string The capability required to access the admin page
     */
    public const CAPABILITY = 'manage_options';

    /**
     * @var string $hookName The hook for the admin page
     */
    private string $hookName;

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
     * @var array<lowercase-string<class-string<AbstractAdminPage>, string>
     */
    private array $submenuHooks;

    /**
     * @var string $slug The slug for the admin page
     */
    private string $slug;

    /**
     * @var string $capability The capability required to access the admin page
     */
    private string $capability;

    /**
     * @var ?AbstractAdminPage $currentSubmenu The current submenu for the admin page
     */
    private ?AbstractAdminPage $currentSubmenu;

    /**
     * @inheritdoc
     */
    public function adminEnqueueScriptHook(): void
    {
        if (!doing_action('admin_enqueue_scripts')) {
            return;
        }
        $hooks = $this->submenuHooks ?? [];
        $handle = $this->container->adminScriptHandle;
        foreach ($hooks as $hook) {
            if (did_action("load-$hook")) {
                wp_enqueue_script_module($handle);
                wp_localize_script(
                    $handle,
                    self::LOCALIZE_KEY,
                    array_map(fn($submenu) => $this->createLocalizeData($submenu), $this->getSubmenus())
                );
                break;
            }
        }
        wp_enqueue_style($handle);
    }

    /**
     * Dispatch the admin menu hook
     */
    public function adminMenuHook(): void
    {
        if (isset($this->hookName)) {
            global $admin_page_hooks;
            if (is_array($admin_page_hooks) && isset($admin_page_hooks[$this->hookName])) {
                return;
            }
        }
        if (!did_action('admin_menu') && !doing_action('admin_menu')) {
            return;
        }
        $slug = $this->getSlug();
        remove_menu_page($slug); // remove it first to avoid duplicate menu items
        $page_hook = add_menu_page(
            $this->getPageTitle(),
            $this->getMenuTitle(),
            $this->getCapability(),
            $slug,
            '',
            $this->getIcon(),
            100
        ) ?: '';
        $this->hookName = $page_hook;
        if (!$page_hook) {
            return;
        }
        // remove first
        remove_action("load-$page_hook", [$this, 'pageHook']);
        // add later
        add_action("load-$page_hook", [$this, 'pageHook']);
        foreach (($this->submenus ?? []) as $key => $submenu) {
            // prevent override
            if ($submenu->getSlug() === $slug && !$submenu instanceof MainMenu) {
                continue;
            }
            $submenu->deregister($this);
            $hook = $submenu->register($this);
            if (!empty($hook)) {
                remove_action("load-$hook", [$this, 'subMenuHook'], PHP_INT_MIN);
                add_action("load-$hook", [$this, 'subMenuHook'], PHP_INT_MIN);
                $this->submenuHooks[$key] = $hook;
            }
        }
    }

    /**
     * AdminMenuPage constructor.
     * @param Container $container
     */
    public function __construct(public readonly Container $container)
    {
        $this->add(MainMenu::class);
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
     * @return array<lowercase-string<class-string<AbstractAdminPage>, string>
     */
    public function getSubmenuHooks(): array
    {
        return $this->submenuHooks ?? [];
    }

    /**
     * Get submenus
     * @template T of AbstractAdminPage
     * @return array<lowercase-string<class-string<T>, T>
     */
    public function getSubmenus(): array
    {
        return $this->submenus;
    }

    /**
     * Get Capability for the admin page
     *
     * @return string
     * @see https://developer.wordpress.org/reference/functions/current_user_can/
     */
    public function getCapability(): string
    {
        return $this->capability ??= self::CAPABILITY;
    }

    /**
     * Get Hook for the admin page
     *
     * @return string
     */
    public function getHookName(): string
    {
        return $this->hookName ??= '';
    }

    /**
     * Get Page Title for the admin page
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        $this->pageTitle ??= __('TrayDigita Headless', 'traydigita');
        $title = apply_filters('traydigita:admin_menu:page_title', $this->pageTitle, $this);
        if (!is_string($title) || empty($title)) {
            $title = $this->pageTitle;
        }
        return $title;
    }

    /**
     * Get Menu Title for the admin page
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        $this->menuTitle ??= __('TrayDigita', 'traydigita');
        $tile = apply_filters('traydigita:admin_menu:menu_title', $this->menuTitle, $this);
        if (!is_string($tile) || empty($tile)) {
            $tile = $this->menuTitle;
        }
        return $tile;
    }

    /**
     * Get Icon for the admin page
     *
     * @return string
     */
    public function getIcon(): string
    {
        $default = $this->container->traydigita->svgIcon(['padding' => 20], true);
        $icon = apply_filters('traydigita:admin_menu:icon', $default, $this);
        if (!is_string($icon) || empty($icon)) {
            $icon = $default;
        }
        return $icon;
    }

    /**
     * Remove submenu page from the admin menu
     *
     * @param string|AbstractAdminPage $key
     * @return AbstractAdminPage|null
     */
    public function remove(string|AbstractAdminPage $key): ?AbstractAdminPage
    {
        $object = is_object($key) ? $key : null;
        if (is_object($key)) {
            $key = get_class($key);
        }
        $key = strtolower(ltrim($key, '\\'));
        // prevent remove
        if ($key === strtolower(MainMenu::class)) {
            return null;
        }
        $submenu = $this->submenus[$key] ?? null;
        $submenu ??= $object;
        if (!$submenu) {
            return null;
        }
        unset($this->submenus[$key], $this->submenuHooks[$key]);
        $submenu->deregister($this);
        return $submenu;
    }

    /**
     * Add submenu page to the admin menu
     *
     * @param string|AbstractAdminPage $page
     * @return AbstractAdminPage
     */
    public function add(string|AbstractAdminPage $page): AbstractAdminPage
    {
        $key = is_string($page) ? strtolower(ltrim($page, '\\')) : strtolower(get_class($page));
        if (isset($this->submenus[$key])) {
            throw InvalidOperationException::adminMenuSubmenuAlreadyExists('add', $page);
        }
        if (is_string($page)) {
            if (!class_exists($page)) {
                throw InvalidOperationException::invalidAdminPage('add', $page);
            }
            if (!is_subclass_of($page, AbstractAdminPage::class)) {
                throw InvalidOperationException::invalidAdminPage('add', $page);
            }
            $instance = Callback::apply(fn() => new $page($this));
            $page = $instance ?? $page;
        }
        if (!$page instanceof AbstractAdminPage) {
            throw InvalidOperationException::invalidAdminPage('add', $page);
        }
        $key = strtolower(get_class($page));
        $this->submenus[$key] = $page;
        return $page;
    }

    /**
     * The page hook
     * @return void
     */
    public function pageHook(): void
    {
        if (empty($this->hookName)) {
            return;
        }
        $page_hook = $this->hookName;
        if (!doing_action("load-$page_hook")) {
            return;
        }
        do_action("traydigita:admin_menu:load", $this);
    }

    /**
     * The submenu hook
     * @return void
     */
    public function subMenuHook(): void
    {
        if (!empty($this->currentSubmenu) || empty($this->submenuHooks)) {
            return;
        }
        $action = current_action();
        if (!$action || !str_starts_with($action, "load-")) {
            return;
        }
        $hook = substr($action, 5);
        $key = array_search($hook, $this->submenuHooks, true);
        if (!$key) {
            return;
        }
        $submenu = $this->getSubmenus()[$key] ?? null;
        $this->currentSubmenu = $submenu;
        if (!$submenu) {
            return;
        }
        do_action("traydigita:admin_menu:load:submenu", $submenu, $this);
    }

    /**
     * @param AbstractAdminPage $adminPage
     * @return array
     */
    public function createLocalizeData(AbstractAdminPage $adminPage): array
    {
        $localize = [
            'slug' => $adminPage->getSlug(),
            'hook' => $adminPage->getHookName(),
            'page_title' => $adminPage->getPageTitle(),
            'menu_title' => $adminPage->getMenuTitle(),
            'script_url' => $adminPage->getTsxURL(),
            'class_name' => get_class($adminPage),
            'translations' => $adminPage->getTranslations()
        ];
        $localized = apply_filters('traydigita:admin_menu:localize_scripts', $localize, $adminPage);
        if ($localized !== $localize && is_array($localized)) {
            $localized['slug'] = $localize['slug'];
            $localized['hook'] = $localize['hook'];
            $localized['page_title'] = is_string($localized['page_title'] ?? null)
                ? $localized['page_title']
                : $localize['page_title'];
            $localized['menu_title'] = is_string($localized['menu_title'] ?? null)
                ? $localized['menu_title']
                : $localize['menu_title'];
            $localized['script_url'] = $localize['script_url'];
            if (!is_array($localized['translations'])) {
                $localized['translations'] = $localize['translations'];
            } else {
                foreach ($localize['translations'] as $key => $item) {
                    $localized['translations'][$key] ??= $item;
                }
            }
            $localize = $localized;
        }
        // make translations object
        $localize['translations'] = (object)$localize['translations'];
        return $localize;
    }

    /**
     * @param AbstractAdminPage|AdminMenu $adminPage
     * @return void
     */
    public function render(AbstractAdminPage|AdminMenu $adminPage): void
    {
        $attributes = [
            'class' => 'wrap',
            'data-section' => 'traydigita-headless',
            'data-slug' => $adminPage->getSlug(),
            'data-hook' => $adminPage->getHookName(),
            'data-loaded' => false
        ];
        $attribute = apply_filters('traydigita:admin_menu:attributes', $attributes, $adminPage, $this->container);
        if (is_array($attribute)) {
            if (isset($attribute['class'])) {
                $attribute['class'] = $this->container->attributes->filterClass(
                    $attribute['class'],
                    $attributes['class']
                );
            }
            $attribute['data-section'] = $attributes['data-section'];
            $attribute['data-slug'] = $attributes['data-slug'];
            $attribute['data-loaded'] = $attributes['data-loaded'];
            $attributes = $attribute;
        }
        $attributes = $this->container->attributes->buildAttributes($attributes);
        echo "<div $attributes></div>";
    }
}
