<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use ReflectionClass;
use Throwable;
use TrayDigita\WP\Headless\Resource\Abstracts\AbstractCoreExtension;
use TrayDigita\WP\Headless\Resource\Abstracts\AbstractExtension;
use TrayDigita\WP\Headless\Resource\Exceptions\InvalidOperationException;
use TrayDigita\WP\Headless\Resource\Interfaces\CoreExtensionInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionsInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Hooks\HookAdminEnqueueScriptsInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Hooks\HookInitInterface;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use TrayDigita\WP\Headless\Resource\Utils\Time;
use function array_reverse;
use function array_shift;
use function class_exists;
use function count;
use function doing_action;
use function get_class;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function ltrim;
use function str_replace;
use function strtolower;
use function time;
use function uasort;
use function wp_localize_script;
use const DIRECTORY_SEPARATOR;

final class Extensions implements ExtensionsInterface, HookInitInterface, HookAdminEnqueueScriptsInterface
{
    /**
     * Localize key
     */
    public const LOCALIZE_KEY = '___TRAYDIGITA_EXTENSIONS___';

    /**
     * Option name for storing active extensions
     */
    private const OPTION_NAME = 'active_extensions';

    /**
     * @template T of AbstractExtension|object
     * List of registered extensions
     *
     * @var array<lowercase-string<class-string<T>>, T|class-string<T>>
     */
    private array $extensions;

    /**
     * @template T of AbstractExtension|object
     * List of lazy deactivated extensions
     *
     * @var array<lowercase-string<class-string<T>>, T>
     */
    private array $lazyDeactivatedExtensions;

    /**
     * @template T of AbstractExtension|object
     * List of active extensions
     *
     * @var array<lowercase-string<class-string<T>>, T>
     */
    private array $activeExtensions;

    /**
     * @template T of ExtensionInterface
     * List of loaded extensions
     *
     * @var array<lowercase-string<class-string<T>, class-string<T>>>
     */
    private array $loadedExtensions;

    /**
     * @template T of ExtensionInterface
     * @var array<lowercase-string<class-string<T>, int> $activeExtensionsData
     * Integer as timestamp of when the extension was activated
     */
    private array $activeExtensionsData;

    /**
     * @var bool $loaded Whether the extension collection has been loaded or not.
     */
    private bool $booted;

    /**
     * @var array{
     *      time: array{
     *          start: float,
     *          end: float,
     *          duration: float
     *     },
     *     prepare: array{
     *          start: float,
     *          end: float,
     *          duration: float
     *     },
     *     boot: array{
     *         start: float,
     *         end: float,
     *         duration: float
     *     }
     * }[] $bootTime The time when the extension collection was booted.
     */
    private array $bootTime;

    /**
     * @template T of ExtensionInterface
     * @var array<lowercase-string<class-string<T>>, Throwable> $errors
     *      The errors that occurred during the boot process.
     */
    private array $errors;

    /**
     * @template T of ExtensionInterface
     * @var array<lowercase-string<class-string<T>>, ReflectionClass<T>> $reflectionCaches
     *      The reflection caches for the extensions.
     */
    private static array $reflectionCaches = [];

    /**
     * @var int MAXIMUM_VALID_CACHES The count of invalid reflections for the extensions.
     */
    private const MAXIMUM_VALID_CACHES = 500;

    /**
     * @var int MAXIMUM_INVALID_CACHES The count of invalid reflections for the extensions.
     */
    private const MAXIMUM_INVALID_CACHES = 1000;

    /**
     * @template T of ExtensionInterface
     * @var array<lowercase-string<class-string<T>>, bool> $invalidReflections
     *      The invalid reflections for the extensions.
     */
    private static array $invalidReflections = [];

    /**
     * @var bool $hookInit
     */
    private bool $hookInit = false;

    /**
     * @inheritdoc
     */
    public function initHook() : void
    {
        if ($this->hookInit) {
            return;
        }
        if (!doing_action('init')) {
            return;
        }
        $this->hookInit = true;
    }

    /**
     * Hook enqueue
     * @return void
     */
    public function adminEnqueueScriptHook() : void
    {
        if (!doing_action('admin_enqueue_scripts')) {
            return;
        }
        wp_localize_script(
            $this->container->adminScriptHandle,
            self::LOCALIZE_KEY,
            $this->getExtensions()
        );
        // todo: add dependencies or variables
    }

    /**
     * Extensions constructor.
     */
    public function __construct(public readonly Container $container)
    {
    }

    /**
     * @inheritdoc
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    public function isCore(ExtensionInterface|string $extension): bool
    {
        $ref = $this->getReflection($extension);
        if (!$ref) {
            return false;
        }
        $file = $ref->getFileName();
        if (DIRECTORY_SEPARATOR !== '/') {
            $file = str_replace('\\', '/', $file);
        }
        return str_starts_with($file, $this->container->pluginDir)
            && $ref->isSubclassOf(AbstractCoreExtension::class);
    }

    /**
     * Make a string / object class name lowercase
     *
     * @template T of ExtensionInterface|object
     * @param string|class-string<T>|T $string
     * @return string|lowercase-string<class-string<T>>
     */
    private function makeLowercase(mixed $string): string
    {
        if (is_object($string)) {
            $string = get_class($string);
        }
        return strtolower(ltrim((string)$string, '\\'));
    }

    /**
     * Compare two values for strict equality
     *
     * @template T of ExtensionInterface|object
     * @param mixed $a
     * @param mixed $b
     * @return bool
     */
    private function sameValue(mixed $a, mixed $b): bool
    {
        return $a === $b;
    }

    /**
     * Get the reflection class for an extension
     *
     * @template T of ExtensionInterface
     * @param class-string<T>|T $class
     * @return ReflectionClass<T>|null
     */
    public function getReflection(string|ExtensionInterface $class): ?ReflectionClass
    {
        if (is_object($class)) {
            if (!$class instanceof AbstractExtension) {
                return null;
            }
            $extension = strtolower($class->reflection->getName());
            if (count(self::$reflectionCaches) > self::MAXIMUM_VALID_CACHES) {
                array_shift(self::$reflectionCaches);
            }
            return self::$reflectionCaches[$extension] = $class->reflection;
        }
        $extension = strtolower(ltrim($class, '\\'));
        if (isset(self::$reflectionCaches[$extension])) {
            return self::$reflectionCaches[$extension];
        }
        if (!class_exists($class)) {
            return null;
        }
        $extensionObject = $this->extensions[$extension] ?? null;
        if ($extensionObject instanceof AbstractExtension) {
            if (count(self::$reflectionCaches) > self::MAXIMUM_VALID_CACHES) {
                array_shift(self::$reflectionCaches);
            }
            return self::$reflectionCaches[$extension] = $extensionObject->reflection;
        }
        if (isset(self::$invalidReflections[$extension])) {
            return null;
        }
        if (count(self::$invalidReflections) > self::MAXIMUM_INVALID_CACHES) {
            array_shift(self::$invalidReflections);
        }
        self::$invalidReflections[$class] = true;
        $reflection = Callback::apply(static fn() => new ReflectionClass($class));
        if (!$reflection?->isInstantiable()
            || !$reflection?->isSubclassOf(AbstractExtension::class)
        ) {
            return null;
        }
        unset(self::$invalidReflections[$class]);
        if (count(self::$reflectionCaches) > self::MAXIMUM_VALID_CACHES) {
            array_shift(self::$reflectionCaches);
        }
        if (count(self::$reflectionCaches) > self::MAXIMUM_VALID_CACHES) {
            array_shift(self::$reflectionCaches);
        }
        self::$reflectionCaches[$extension] = $reflection;
        $extension = strtolower($reflection->getName());
        return self::$reflectionCaches[$extension] = $reflection;
    }

    /**
     * Check if an extension satisfies the requirements
     *
     * @template T of ExtensionInterface
     * @param T|class-string<T> $extension
     * @return class-string<T>|null
     */
    private function satisfyClassName(ExtensionInterface|string $extension): ?string
    {
        if (is_string($extension)) {
            $reflection = $this->getReflection($extension);
            return $reflection?->getName();
        }
        if (!$extension instanceof AbstractExtension) {
            return null;
        }
        // strict check if the extension belongs to this collection
        if ($extension->extensions !== $this) {
            return null;
        }
        return get_class($extension);
    }

    /**
     * @inheritdoc
     */
    public function booted(string|ExtensionInterface $extension, bool $strict = false): bool
    {
        $key = $this->makeLowercase($extension);
        if (!isset($this->extensions[$key])) {
            return false;
        }
        if (!isset($this->loadedExtensions[$key])) {
            return false;
        }
        if ($strict && is_object($extension) && !is_string($this->loadedExtensions[$key])) {
            return $this->sameValue($this->loadedExtensions[$key], $extension);
        }
        return true;
    }

    /**
     * @template T of AbstractExtension
     * @param T|class-string<T> $extension
     * @param bool $override
     * @return bool
     * @inheritdoc
     */
    public function register(ExtensionInterface|string $extension, bool $override = true): bool
    {
        if ($this->isBooted()) {
            throw InvalidOperationException::extensionAlreadyBooted('register', $extension);
        }
        $className = $this->satisfyClassName($extension);
        if (!$className) {
            return false;
        }
        $key = $this->makeLowercase($extension);
        if (isset($this->loadedExtensions[$key])) {
            return false;
        }
        $exists = isset($this->extensions[$key]);
        if ($override || !$exists) {
            if (is_object($extension)) {
                if (!$extension->isCore() || !$exists) {
                    $this->extensions[$key] = $extension;
                    return true;
                }
                return false;
            }
            if ($this->isCore($className)) {
                $extension = new $className($this);
            }
            $this->extensions[$key] = is_object($extension) ? $extension : $className;
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     * @throws InvalidOperationException if the extension is loaded and strict is true
     */
    public function remove(string|ExtensionInterface $extension, bool $strict = false): ?ExtensionInterface
    {
        if ($this->isBooted()) {
            throw InvalidOperationException::extensionAlreadyBooted('remove', $extension);
        }
        $key = $this->makeLowercase($extension);
        if (!isset($this->extensions[$key])) {
            return null;
        }
        if (isset($this->loadedExtensions[$key])) {
            throw InvalidOperationException::extensionAlreadyLoaded(
                'remove',
                $this->loadedExtensions[$key]
            );
        }
        $removedExtension = $this->extensions[$key];
        if (is_string($removedExtension) && $this->isCore($removedExtension)) {
            $removedExtension = $this->get($removedExtension);
        }
        if (is_object($removedExtension)) {
            if ($removedExtension->isCore() && $removedExtension instanceof CoreExtensionInterface) {
                if ($removedExtension->shouldBeActive()) {
                    throw InvalidOperationException::extensionCoreCannotBeRemoved(
                        'remove',
                        $removedExtension
                    );
                }
            }
        }
        if ($strict && is_object($extension) && !is_string($removedExtension)) {
            if (!$this->sameValue($removedExtension, $extension)) {
                return null;
            }
        }
        unset($this->extensions[$key], $this->lazyDeactivatedExtensions[$key]);
        return $removedExtension;
    }

    /**
     * @inheritdoc
     */
    public function has(string|ExtensionInterface $extension, bool $strict = false): bool
    {
        $key = $this->makeLowercase($extension);
        if (!isset($this->extensions[$key])) {
            return false;
        }
        if ($strict && is_object($extension) && !is_string($this->extensions[$key])) {
            return $this->sameValue($this->extensions[$key], $extension);
        }
        return true;
    }

    /**
     * @param string|ExtensionInterface $name
     * @param bool $strict
     * @inheritdoc
     */
    public function get(string|ExtensionInterface $name, bool $strict = false): ?ExtensionInterface
    {
        $key = $this->makeLowercase($name);
        if (!isset($this->extensions[$key])) {
            return null;
        }
        if ($strict && is_object($name) && !is_string($this->extensions[$key])) {
            if (!$this->sameValue($this->extensions[$key], $name)) {
                return null;
            }
        }
        $extension = $this->extensions[$key];
        if (is_string($extension)) {
            $extension = new $extension($this);
            $this->extensions[$key] = $extension;
        }
        return $this->extensions[$key];
    }

    /**
     * @inheritdoc
     */
    public function getExtensions(): array
    {
        return $this->extensions ?? [];
    }

    /**
     * @inheritdoc
     */
    public function activate(string|ExtensionInterface $name, bool $strict = false): ?int
    {
        if ($this->isBooted()) {
            throw InvalidOperationException::extensionAlreadyBooted('activate', $name);
        }
        $extension = $this->get($name, $strict);
        if (!$extension) {
            return null;
        }
        $activeExtensions = $this->getActiveExtensions();
        $key = $this->makeLowercase($name);
        if (isset($activeExtensions[$key])) {
            return $activeExtensions[$key];
        }
        $activeExtensions[$key] = time();
        $this->container->options->setOption(self::OPTION_NAME, $activeExtensions);
        return $activeExtensions[$key];
    }

    /**
     * @inheritdoc
     */
    public function deactivate(string|ExtensionInterface $name, bool $strict = false): ?int
    {
        if ($this->isBooted()) {
            throw InvalidOperationException::extensionAlreadyBooted('deactivate', $name);
        }
        $extension = $this->get($name, $strict);
        if (!$extension) {
            return null;
        }
        if ($extension instanceof CoreExtensionInterface && $extension->isCore()) {
            // core can not be deactivated, but if it should be active, we can ignore it
            if ($extension->shouldBeActive()) {
                return null;
            }
        }
        $activeExtensions = $this->getActiveExtensionData();
        $key = $this->makeLowercase($name);
        if (!isset($activeExtensions[$key])) {
            return null;
        }
        $timestamp = $activeExtensions[$key];
        unset($activeExtensions[$key]);
        $this->activeExtensionsData = $activeExtensions;
        if (isset($this->activeExtensions[$key])) {
            unset($this->activeExtensions[$key]);
            unset($this->lazyDeactivatedExtensions[$key]);
        } elseif (!isset($this->lazyDeactivatedExtensions[$key])) {
            $this->lazyDeactivatedExtensions[$key] = $extension;
        }
        $this->container->options->setOption(self::OPTION_NAME, $activeExtensions);
        return $timestamp;
    }

    /**
     * Get the active extension data from the options
     *
     * @return array<lowercase-string<class-string<ExtensionInterface>>, int>
     */
    private function getActiveExtensionData(): array
    {
        if (isset($this->activeExtensionsData)) {
            return $this->activeExtensionsData;
        }
        $options = $this->container->options;
        $activeExtensions = $options->getOption(self::OPTION_NAME);
        $update = false;
        if (!is_array($activeExtensions)) {
            $activeExtensions = [];
            $update = true;
        }
        foreach ($activeExtensions as $key => $value) {
            if (!is_string($key) || !is_int($value)) {
                unset($activeExtensions[$key]);
                $update = true;
            }
        }
        if ($update) {
            $options->setOption(self::OPTION_NAME, $activeExtensions);
        }
        // sort by timestamp
        uasort($activeExtensions, static fn($a, $b) => $a <=> $b);
        $this->activeExtensionsData = $activeExtensions;
        return $this->activeExtensionsData;
    }

    /**
     * @inheritdoc
     */
    public function getActiveExtensions(): array
    {
        if (isset($this->activeExtensions)) {
            return $this->activeExtensions;
        }
        $this->activeExtensions = [];
        foreach ($this->getActiveExtensionData() as $key => $timestamp) {
            $extension = $this->get($key);
            if (!$extension) {
                unset($this->activeExtensionsData[$key]);
                continue;
            }
            if (($this->lazyDeactivatedExtensions[$key] ?? null) !== $extension) {
                $this->activeExtensions[$key] = $extension;
            }
        }
        $this->lazyDeactivatedExtensions = [];
        return $this->activeExtensions;
    }

    /**
     * @inheritdoc
     */
    public function getBootErrors(): array
    {
        return $this->errors ?? [];
    }

    /**
     * @inheritdoc
     */
    public function isBooted(): bool
    {
        return !empty($this->booted);
    }

    /**
     * @inheritdoc
     */
    public function getBootTimeNano(): array
    {
        return $this->bootTime ?? [];
    }

    /**
     * @inheritdoc
     */
    public function shutdown(): void
    {
        $this->booted = false;
    }

    /**
     * @inheritdoc
     */
    public function bootCount(): int
    {
        return count($this->bootTime ?? []);
    }

    /**
     * @inheritdoc
     */
    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }
        $this->bootTime ??= [];
        $increment = count($this->bootTime ?? []);
        $this->bootTime[$increment] = [
            'time' => [
                'start' => Time::nano(),
                'end' => 0,
                'duration' => 0,
            ],
            'prepare' => [
                'start' => 0,
                'end' => 0,
                'duration' => 0,
            ],
            'boot' => [
                'start' => 0,
                'end' => 0,
                'duration' => 0,
            ]
        ];
        $boot = &$this->bootTime[$increment];
        $this->booted = true;
        $extensions = [];
        $cores = [];
        $activeExtensions = $this->getActiveExtensions();
        foreach ($this->extensions as $key => $extension) {
            $extension = is_object($extension) ? $extension : $this->get($key);
            if (!$extension) {
                continue;
            }
            if ($extension->isCore()) {
                if ($extension->shouldBeActive()) {
                    $extensions[$key] = $extension;
                } elseif (isset($activeExtensions[$key])) {
                    $cores[$key] = $extension;
                }
            }
        }
        $update = false;
        // sort by highest priority first
        uasort($extensions, static fn($a, $b) => $b->getPriority() <=> $a->getPriority());
        // cores should be sorted by highest priority first, but they should be added to the end of the list
        uasort($cores, static fn($a, $b) => $b->getPriority() <=> $a->getPriority());

        $boot['prepare']['start'] = Time::nano();
        // execute cores after the extensions, but they should be added to the end of the list
        foreach ($extensions as $key => $extension) {
            unset($this->extensions[$key]);
            try {
                $extension->prepare($this);
                $this->activeExtensions[$key] = $extension;
                if (!isset($this->activeExtensionsData[$key])) {
                    $this->activeExtensionsData[$key] = time();
                    $update = true;
                }
            } catch (Throwable $e) {
                $update = $update || isset($this->activeExtensionsData[$key]);
                unset($this->activeExtensionsData[$key], $extensions[$key]);
                $this->errors[$key] = $e;
            }
        }
        foreach (array_reverse($cores) as $key => $extension) {
            unset($this->extensions[$key], $cores[$key]);
            $this->extensions = [$key => $extension] + $this->extensions;
        }
        foreach (array_reverse($extensions) as $key => $extension) {
            unset($this->extensions[$key]);
            $this->extensions = [$key => $extension] + $this->extensions;
        }
        $this->lazyDeactivatedExtensions = [];
        $options = $this->container->get(Options::class);
        foreach ($activeExtensions as $key => $extension) {
            if (isset($extensions[$key])) {
                continue;
            }
            try {
                $extension->prepare($this);
                $this->activeExtensions[$key] = $extension;
                $extensions[$key] = $extension;
            } catch (Throwable $e) {
                $update = true;
                unset(
                    $this->activeExtensionsData[$key],
                    $this->activeExtensions[$key]
                );
                $this->errors[$key] = $e;
            }
        }
        $this->activeExtensions = $extensions;
        $activeExtensions = $this->activeExtensionsData;
        foreach ($activeExtensions as $key => $timestamp) {
            if (isset($extensions[$key])) {
                continue;
            }
            $extension = $this->get($key);
            if (!$extension) {
                unset($activeExtensions[$key]);
                continue;
            }
            $update = true;
            try {
                $this->activeExtensionsData = $activeExtensions;
                $extension->prepare($this);
                $this->activeExtensionsData = $activeExtensions;
                $extensions[$key] = $extension;
                $this->activeExtensions[$key] = $extension;
            } catch (Throwable $e) {
                unset($activeExtensions[$key]);
                $this->errors[$key] = $e;
            }
        }

        $boot['prepare']['end'] = Time::nano();
        $boot['prepare']['duration'] = $boot['prepare']['end'] - $boot['prepare']['start'];
        $boot['boot']['start'] = Time::nano();
        foreach ($extensions as $key => $extension) {
            try {
                $this->activeExtensionsData = $activeExtensions;
                $extension->boot($this);
                $this->activeExtensionsData = $activeExtensions;
                $this->loadedExtensions[$key] = get_class($extension);
                $this->activeExtensions[$key] = $extension;
            } catch (Throwable $e) {
                $update = true;
                unset($extensions[$key], $activeExtensions[$key], $this->activeExtensions[$key]);
                $this->errors[$key] = $e;
            }
        }
        $boot['boot']['end'] = Time::nano();
        $boot['boot']['duration'] = $boot['boot']['end'] - $boot['boot']['start'];
        $this->activeExtensionsData = $activeExtensions;
        $this->activeExtensions = $extensions; // re-set active extensions to the final list
        if ($update) {
            $options->setOption(self::OPTION_NAME, $activeExtensions);
        }
        $boot['time']['end'] = Time::nano();
        $boot['time']['duration'] = $boot['time']['end'] - $boot['time']['start'];
        unset($boot);
    }
}
