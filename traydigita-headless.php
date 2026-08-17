<?php
/**
 * Plugin Name: TrayDigita Headless
 * Plugin URI: https://traydigita.com
 * Description: A headless plugin for WordPress
 * Version: 1.0.0
 * Text Domain: traydigita
 * Domain Path: /languages
 * Author: TrayDigita
 * Author URI: https://traydigita.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

(function () {

    if (!defined('ABSPATH')) {
        return;
    }

    add_action('after_setup_theme', function () {
        load_plugin_textdomain(
            'traydigita',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    });

    if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
        global $pagenow;
        if ($pagenow === 'plugins.php') {
            add_action(
                'admin_notices',
                static function () {
                    echo '<div class="notice notice-error"><p>';
                    // phpcs:disable Generic.Files.LineLength.TooLong
                    echo esc_html__(
                        'TrayDigita Headless plugin requires Composer dependencies to be installed. Please run "composer install" in the plugin directory.',
                        'traydigita'
                    );
                    echo '</p></div>';
                }
            );
        }
        return;
    }

    require __DIR__ . '/vendor/autoload.php';
    if (!class_exists('TrayDigita\WP\Headless\Headless')) {
        // require after autoload to ensure that the autoloader is available for the plugin
        require_once __DIR__ . '/Headless.php';
    }
    add_action('plugin_loaded', [TrayDigita\WP\Headless\Headless::getInstance(), 'pluginLoadedHook']);
})();
