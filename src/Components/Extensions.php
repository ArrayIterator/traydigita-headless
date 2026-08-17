<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use ReflectionClass;
use Throwable;
use TrayDigita\WP\Headless\Resource\Abstracts\AbstractExtension;
use TrayDigita\WP\Headless\Resource\Exceptions\InvalidOperationException;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionsInterface;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function class_exists;
use function get_class;
use function is_array;
use function is_int;
use function is_object;
use function is_string;
use function ltrim;
use function microtime;
use function strtolower;
use function time;
use function uasort;

class Extensions implements ExtensionsInterface
{
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
     *      prepare: array{
     *          start: float,
     *          end: float,
     *          duration: float
     *     },
     *     boot: array{
     *         start: float,
     *         end: float,
     *         duration: float
     *     }
     * } $bootTime The time when the extension collection was booted.
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
     * @var array<lowercase-string<class-string<T>>, ReflectionClass<T>|false> $reflectionCaches
     *      The reflection caches for the extensions.
     */
    private static array $reflectionCaches;

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
            return self::$reflectionCaches[$extension] = $class->reflection;
        }

        $extension = strtolower(ltrim($class, '\\'));
        if (isset(self::$reflectionCaches[$extension])) {
            return self::$reflectionCaches[$extension] ?: null;
        }
        if (!class_exists($class)) {
            return null;
        }
        self::$reflectionCaches[$class] = false;
        $reflection = Callback::apply(static fn () => new ReflectionClass($class));
        if (!$reflection?->isInstantiable()
            || !$reflection?->isSubclassOf(AbstractExtension::class)
        ) {
            return null;
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
        if ($override || !isset($this->extensions[$key])) {
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
        if ($strict && is_object($extension) && !is_string($removedExtension)) {
            if (!$this->sameValue($removedExtension, $extension)) {
                return null;
            }
        }
        unset($this->extensions[$key]);
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
        $activeExtensions = $this->getActiveExtensions();
        $key = $this->makeLowercase($name);
        if (!isset($activeExtensions[$key])) {
            return null;
        }
        $timestamp = $activeExtensions[$key];
        unset($activeExtensions[$key]);
        $this->activeExtensionsData = $activeExtensions;
        if (!isset($this->lazyDeactivatedExtensions[$key])) {
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
     * @return ?array{
     *     prepare: array{
     *          start: float,
     *          end: float,
     *          duration: float
     *     },
     *     boot: array{
     *          start: float,
     *          end: float,
     *          duration: float
     *     }
     * }
     */
    public function getBootTime(): ?array
    {
        return $this->bootTime ?? [];
    }

    /**
     * @inheritdoc
     */
    public function boot(): void
    {
        if ($this->isBooted()) {
            return;
        }
        $this->bootTime = [
            'prepare' => [
                'start' => microtime(true),
                'end' => 0,
                'duration' => 0,
            ],
            'boot' => [
                'start' => 0,
                'end' => 0,
                'duration' => 0,
            ]
        ];
        $this->booted = true;
        $this->lazyDeactivatedExtensions = [];
        $options = $this->container->get(Options::class);
        $extensions = $this->getActiveExtensions();
        $update = false;
        foreach ($extensions as $key => $extension) {
            try {
                $extension->prepare($this);
            } catch (Throwable $e) {
                $update = true;
                unset($extensions[$key], $this->activeExtensionsData[$key]);
                $this->errors[$key] = $e;
            }
        }
        $end = microtime(true);
        $this->bootTime['prepare']['end'] = $end;
        $this->bootTime['prepare']['duration'] = $end - $this->bootTime['prepare']['start'];
        $this->bootTime['boot']['start'] = microtime(true);
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
            } catch (Throwable $e) {
                unset($activeExtensions[$key]);
                $this->errors[$key] = $e;
            }
        }
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
        $this->bootTime['boot']['end'] = microtime(true);
        $this->bootTime['boot']['duration'] = $this->bootTime['boot']['end'] - $this->bootTime['boot']['start'];
        $this->activeExtensionsData = $activeExtensions;
        if ($update) {
            $options->setOption(self::OPTION_NAME, $activeExtensions);
        }
    }
}
