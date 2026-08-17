<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless;

use Throwable;
use TrayDigita\WP\Headless\Extensions\GraphQL;
use TrayDigita\WP\Headless\Extensions\PopularPosts;
use TrayDigita\WP\Headless\Resource\Components\Container;
use TrayDigita\WP\Headless\Resource\Components\Extensions;
use TrayDigita\WP\Headless\Resource\TrayDigita;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function add_action;
use function debug_backtrace;
use function defined;
use function did_action;
use function do_action;
use function doing_action;
use function file_exists;
use function file_get_contents;
use function is_string;
use function json_decode;
use function remove_action;
use function str_starts_with;
use const DEBUG_BACKTRACE_IGNORE_ARGS;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_MIN;

/**
 * @mixin TrayDigita
 */
final class Headless
{
    public const DEVELOPMENT_FILE = __DIR__ . '/.development.php';

    /**
     * @var string DEVELOPMENT_SERVER_FILE path to the development server file
     */
    public const DEVELOPMENT_SERVER_FILE = __DIR__ . '/.server.json';

    /**
     * @var self
     */
    private static self $instance;

    /**
     * @var TrayDigita
     */
    public readonly TrayDigita $traydigita;

    /**
     * @var bool $initialized indicates whether the initialization has been completed
     */
    private bool $initialized = false;

    /**
     * @var bool $headlessInitialized indicates whether the headless initialization has been completed
     */
    private bool $headlessInitialized = false;

    /**
     * @var bool $isDev indicates whether the plugin is in development mode
     */
    private bool $isDev;

    /**
     * @var array<class-string<Resource\Abstracts\AbstractExtension>>
     */
    public const CORE_EXTENSIONS = [
        GraphQL::class,
        PopularPosts::class
    ];

    /**
     * @var string $developmentFile path to the development file
     */
    public readonly string $developmentFile;

    /**
     * @var string $serverJsonFile path to the development server JSON file
     */
    public readonly string $serverJsonFile;

    /**
     * @var array|null|false $serverJson the parsed JSON data from the development server file
     */
    private array|null|false $serverJson;

    /**
     * Constructor
     */
    private function __construct()
    {
        self::$instance = $this;

        $this->developmentFile = self::DEVELOPMENT_FILE;
        $this->serverJsonFile = self::DEVELOPMENT_SERVER_FILE;

        // setup
        $plugin_dir = __DIR__;
        $plugin_file = __FILE__;
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) as $debug) {
            $file = $debug['file'] ?? null;
            if (!is_string($file) || $file === $plugin_file) {
                continue;
            }
            if (str_starts_with($file, $plugin_dir . DIRECTORY_SEPARATOR)) {
                $plugin_file = $file;
                break;
            }
        }
        $this->traydigita = (new Container(
            $this->isDev(),
            $this->getServerJson(),
            $plugin_file
        ))->traydigita;
        if ($this->isDev() && file_exists($this->developmentFile)) {
            Callback::apply(static function ($file) {
                require_once $file;
            }, $this->developmentFile);
        }
    }

    /**
     * Get the parsed JSON data from the development server file
     *
     * @return array|null
     */
    public function getServerJson(): ?array
    {
        if (!$this->isDev()) {
            return null;
        }
        if (isset($this->serverJson)) {
            return $this->serverJson?:null;
        }
        $this->serverJson = Callback::apply(function ($file) {
            return file_exists($file) ? json_decode(file_get_contents($file), true) : null;
        }, $this->serverJsonFile)?:null;
        return $this->serverJson;
    }

    /**
     * Initialize the Headless instance
     */
    public function hookPluginLoaded(): self
    {
        if ($this->initialized) {
            return $this;
        }
        if (!doing_action('plugin_loaded') && !did_action('plugin_loaded')) {
            return $this;
        }
        $this->initialized = true;
        remove_action('plugin_loaded', [$this, 'hookPluginLoaded']);
        do_action('traydigita:headless_init', $this);
        add_action('traydigita:init', [$this, 'hookHeadlessInit'], PHP_INT_MIN);
        if (doing_action('plugins_loaded') || did_action('plugins_loaded')) {
            $this->traydigita->init();
        } else {
            add_action('plugins_loaded', [$this->traydigita, 'init']);
        }
        return $this;
    }

    /**
     * Hook to initialize core extensions during headless initialization
     *
     * @param mixed $result
     * @return mixed
     */
    public function hookHeadlessInit(mixed $result): mixed
    {
        if ($this->headlessInitialized) {
            return $result;
        }
        if (!doing_action('traydigita:headless_init')) {
            return $result;
        }
        $this->headlessInitialized = true;
        $extensions = $this->extensions;
        if (!$extensions instanceof Extensions) {
            return $result;
        }
        foreach (self::CORE_EXTENSIONS as $extension) {
            try {
                $extensions->register($extension);
            } catch (Throwable) {
                // Ignore any exceptions thrown during registration
            }
        }
        return $result;
    }

    /**
     * Check if the plugin is in development mode
     *
     * @return bool
     */
    public function isDev(): bool
    {
        if (isset($this->isDev)) {
            return $this->isDev;
        }
        $this->isDev =
            defined('TRAYDIGITA_DEBUG')
            && \TRAYDIGITA_DEBUG
            && file_exists($this->developmentFile)
            && file_exists(self::DEVELOPMENT_SERVER_FILE);
        return $this->isDev;
    }

    /**
     * Get the singleton instance of Headless
     *
     * @return self
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function __call(string $name, array $arguments)
    {
        return $this->traydigita->$name(...$arguments);
    }

    public function __set(string $name, $value): void
    {
        $this->traydigita->$name = $value;
    }

    public function __get(string $name)
    {
        return $this->traydigita->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->traydigita->$name);
    }
}
