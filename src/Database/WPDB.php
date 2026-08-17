<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Database;

use function defined;
use function is_string;
use function is_wp_error;

/**
 * @mixin \wpdb
 */
final class WPDB
{
    private static \wpdb $static_wpdb;

    private \wpdb $wpdb;

    public function __construct(\wpdb|WPDB|WordPressDatabase $wpdb = null)
    {
        if ($wpdb instanceof \wpdb) {
            $this->wpdb = $wpdb;
        } elseif ($wpdb instanceof WPDB) {
            $this->wpdb = $wpdb->wpdb();
        } elseif ($wpdb instanceof WordPressDatabase) {
            $this->wpdb = $wpdb->getWPDB();
        }
    }

    public function wpdb() : \wpdb
    {
        if (!isset($this->wpdb)) {
            if (isset(self::$static_wpdb)) {
                $this->wpdb = self::$static_wpdb;
            } else {
                global $wpdb;
                if ($wpdb instanceof \wpdb) {
                    $new_wpdb = $wpdb;
                } else {
                    global $table_prefix;
                    $dbuser = defined('DB_USER') ? DB_USER : '';
                    $dbpassword = defined('DB_PASSWORD') ? DB_PASSWORD : '';
                    $dbname = defined('DB_NAME') ? DB_NAME : '';
                    $dbhost = defined('DB_HOST') ? DB_HOST : '';
                    $new_wpdb = new \wpdb($dbuser, $dbpassword, $dbname, $dbhost);
                    $pref = null;
                    if (is_string($table_prefix)) {
                        $pref = $new_wpdb->set_prefix($table_prefix);
                        if (is_wp_error($pref)) {
                            $pref = null;
                        }
                    }
                    if (!$pref) {
                        if (defined('DB_PREFIX')) {
                            $new_wpdb->set_prefix(\DB_PREFIX);
                        }
                    }
                }
                self::$static_wpdb = $this->wpdb = $new_wpdb;
            }
        }
        return $this->wpdb;
    }

    public function __call(string $name, array $arguments)
    {
        return $this->wpdb()->$name(...$arguments);
    }

    public function __set(string $name, $value): void
    {
        $this->wpdb()->$name = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->wpdb()->$name);
    }

    public function __get(string $name)
    {
        return $this->wpdb()->$name;
    }
}
