<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces;

use TrayDigita\WP\Headless\Resource\Components\Container;

interface ExtensionsInterface
{
    /**
     * ExtensionsInterface constructor.
     * @param Container $container
     */
    public function __construct(Container $container);

    /**
     * Get the container instance
     *
     * @return Container
     */
    public function getContainer() : Container;

    /**
     * Check if an extension is loaded
     * @template T of ExtensionInterface
     *
     * @param class-string<T>|lowercase-string<class-string<T>>|T $extension
     * @param bool $strict if true, will check if the extension is loaded and is an instance of the given class
     * @return bool
     */
    public function booted(string|ExtensionInterface $extension, bool $strict = false): bool;

    /**
     * Add an extension to the collection
     *
     * @param ExtensionInterface $extension The extension to add
     * @param bool $override If true, will override the existing extension if it exists
     * @return bool True if the extension was added, false if it already exists and override is false
     * @throws InvalidOperationExceptionInterface if the extension is already booted and override is false
     */
    public function register(ExtensionInterface $extension, bool $override = true) : bool;

    /**
     * Remove an extension from the collection, if already loaded, ignore it
     *
     * @template T of ExtensionInterface
     * @param class-string<T>|lowercase-string<class-string<T>>|T $extension
     * @param bool $strict if true, will check if the extension is loaded and is an instance of the given class
     * @return ?T the removed extension, or null if not found
     * @throws InvalidOperationExceptionInterface if the extension is loaded and strict is true
     */
    public function remove(string|ExtensionInterface $extension, bool $strict = false): ?ExtensionInterface;

    /**
     *  Check if an extension is in the collection
     *
     * @template T of ExtensionInterface
     * @param class-string<T>|lowercase-string<class-string<T>>|T $extension
     * @param bool $strict if true, will check if the extension is loaded and is an instance of the given class
     * @return bool
     */
    public function has(string|ExtensionInterface $extension, bool $strict = false): bool;

    /**
     * Get an extension from the collection by name
     *
     * @template T of ExtensionInterface
     * @param class-string<T>|lowercase-string<class-string<T>>|T $name
     * @param bool $strict if true, will check if the extension is loaded and is an instance of the given class
     * @return T|null
     */
    public function get(string|ExtensionInterface $name, bool $strict = false): ?ExtensionInterface;

    /**
     *  Get all extensions in the collection
     * @template T of ExtensionInterface
     * @return array<lowercase-string<class-string<T>>, T>
     */
    public function getExtensions(): array;

    /**
     * Get all active extensions in the collection
     * @template T of ExtensionInterface
     * @return array<lowercase-string<class-string<T>>, T>
     */
    public function getActiveExtensions() : array;

    /**
     * Activate an extension by name
     *
     * @template T of ExtensionInterface
     * @param class-string<T>|lowercase-string<class-string<T>>|T $name
     * @param bool $strict if true, will check if the extension is loaded and is an instance of the given class
     * @return ?int The timestamp when the extension was activated, or null if it was not found or already activated
     * @throws InvalidOperationExceptionInterface if the extension is already booted and strict is true
     */
    public function activate(string|ExtensionInterface $name, bool $strict = false): ?int;

    /**
     * Deactivate an extension by name
     *
     * @template T of ExtensionInterface
     * @param class-string<T>|lowercase-string<class-string<T>>|T $name
     * @param bool $strict if true, will check if the extension is loaded and is an instance of the given class
     * @return ?int The timestamp when the extension was deactivated, or null if it was not found or already deactivated
     * @throws InvalidOperationExceptionInterface if the extension is already booted and strict is true
     */
    public function deactivate(string|ExtensionInterface $name, bool $strict = false): ?int;

    /**
     * @template T of ExtensionInterface
     * Get the boot errors of the extension collection
     *
     * @return array<lowercase-string<class-string<T>>, \Throwable>
     *     An associative array of extension names and their corresponding error messages
     */
    public function getBootErrors() : array;

    /**
     * Get the boot time of the extension collection, this returning nanoseconds integer
     *
     * @return array{
     *     time: array{
     *           start: int,
     *           end: int,
     *           duration: int
     *      },
     *     prepare: array{
     *          start: int,
     *          end: int,
     *          duration: int
     *     },
     *     boot: array{
     *          start: int,
     *          end: int,
     *          duration: int
     *     }
     * }[]
     */
    public function getBootTimeNano() : array;

    /**
     * Check if the extension collection is booted
     *
     * @return bool
     */
    public function isBooted(): bool;

    /**
     * Load the extension collection
     */
    public function boot();

    /**
     * Shutdown the extension collection
     */
    public function shutdown();

    /**
     * Get the number of booted extensions
     *
     * @return int
     */
    public function bootCount(): int;

    /**
     * Check if the extension is a core extension
     *
     * @template T of ExtensionInterface
     * @param T|class-string<T> $extension
     * @return bool
     */
    public function isCore(ExtensionInterface|string $extension): bool;
}
