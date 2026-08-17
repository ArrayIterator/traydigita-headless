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
     * @noinspection PhpMissingReturnTypeInspection
     */
    function traydigita_development_assets_manifest_definition($definition, $name, $type, $container)
    {
        if (!is_array($definition)) {
            return $definition;
        }
        if (!$container->is_development) {
            return $definition;
        }
        $json = $container->developmentServersJson;
        if (!$json || !is_string($json['url'] ?? null)) {
            return $definition;
        }
        $url = trailingslashit($json['url']);
        foreach ($definition['files'] as &$file) {
            $file['url'] = $url . $file['path'];
        }
        unset($file);
        $definition['url'] = $url . $definition['path'];
        return $definition;
    }
}
add_filter('traydigita:assets:manifest:definition', 'traydigita_development_assets_manifest_definition', 10, 4);

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
