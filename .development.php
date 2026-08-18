<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    return;
}

if (!function_exists('traydigita_development_assets_manifest_definition')) {
    /**
     * @template TContainer of TrayDigita\WP\Headless\Resource\Components\Container
     * @template TDef of array{
     *       name: string,
     *       entryName: string,
     *       url : string,
     *       file: string,
     *       path: string,
     *       size: int,
     *       files: array{
     *           file: string,
     *           path: string,
     *           url: string,
     *           size: int,
     *       }[]
     *   } $definition
     * @param TDef $definition
     * @param string $name
     * @param "js"|"css" $type
     * @param TrayDigita\WP\Headless\Resource\Components\Container $container
     * @return TDef
     * @noinspection PhpMissingParamTypeInspection
     */
    function traydigita_development_assets_manifest_definition(array $definition, string $name, string $type, $container) : array
    {
        if (!$container->is_development) {
            return $definition;
        }
        $devInfo = $container->development_server_info->getStatus();
        if (!$devInfo['running']) {
            return $definition;
        }
        $url = trailingslashit($devInfo['url']);
        foreach ($definition['files'] as &$file) {
            $file['url'] = $url . $file['path'];
        }
        unset($file);
        $definition['url'] = $url . $definition['path'];
        return $definition;
    }
}
add_filter('traydigita:assets:manifest:definition', 'traydigita_development_assets_manifest_definition', 10, 4);

if (!function_exists('traydigita_development_admin_menu_menu_title')) {
    /**
     * @param string $title
     * @param TrayDigita\WP\Headless\Resource\Components\AdminMenu $adminMenu
     * @return string
     */
    function traydigita_development_admin_menu_menu_title(string $title, TrayDigita\WP\Headless\Resource\Components\AdminMenu $adminMenu) : string
    {
        $status = $adminMenu->container->development_server_info->getStatus();
        if ($status['running']) {
            $title .= ' <span class="traydigita-dev-status-running"></span>';
            return sprintf(
                '<span class="traydigita-dev-status-menu-title" title="%s: %s">%s</span>',
                __('Development Server Running', 'traydigita'),
                esc_attr($status['url']),
                $title
            );
        }
        return $title;
    }
}
add_filter('traydigita:admin_menu:menu_title', 'traydigita_development_admin_menu_menu_title', 10, 2);

if (!function_exists('traydigita_development_plugins_before_init')) {
    /**
     * @noinspection PhpMissingReturnTypeInspection
     */
    function traydigita_development_plugins_before_init($traydigita)
    {
        /**
         * @var TrayDigita\WP\Headless\Resource\TrayDigita $traydigita
         */
        /** @noinspection PhpIfWithCommonPartsInspection */
        if (!$traydigita->is_development) {
            return $traydigita;
        }
        // tests
        // $traydigita->assets->getJsManifest('traydigita-headless');
        return $traydigita;
    }
}
add_action('traydigita:init:before', 'traydigita_development_plugins_before_init');
