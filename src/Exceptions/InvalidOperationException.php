<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Exceptions;

use LogicException;
use PHPStan\DependencyInjection\ExtensionInterface;
use TrayDigita\WP\Headless\Resource\Abstracts\AbstractAdminPage;
use TrayDigita\WP\Headless\Resource\Interfaces\InvalidOperationExceptionInterface;
use function get_class;
use function sprintf;

class InvalidOperationException extends LogicException implements InvalidOperationExceptionInterface
{
    /**
     * @param string $operation
     * @param string|ExtensionInterface $className
     * @return static
     */
    public static function extensionAlreadyLoaded(
        string $operation,
        string|ExtensionInterface $className,
    ): static {
        return new static(
            sprintf(
                // translators: 1: The operation, 2. The object name
                __('Cannot %1$s extension %2$s because it is already loaded', 'traydigita'),
                $operation,
                is_string($className) ? $className : get_class($className)
            )
        );
    }

    /**
     * @param string $operation
     * @param string|ExtensionInterface $className
     * @return static
     */
    public static function extensionAlreadyBooted(
        string $operation,
        string|ExtensionInterface $className,
    ): static {
        return new static(
            sprintf(
                // translators: 1: The operation, 2. The object name
                __('Cannot %1$s extension %2$s because it is already booted', 'traydigita'),
                $operation,
                is_string($className) ? $className : get_class($className)
            )
        );
    }

    /**
     * Exception for invalid user operation
     *
     * @param string $operation
     * @return static
     */
    public static function invalidUser(
        string $operation,
    ): static {
        return new static(
            sprintf(
                // translators: %s as operation name
                __('Cannot %s because the user is invalid', 'traydigita'),
                $operation
            )
        );
    }

    /**
     * Cannot remove core extension exception
     *
     * @param string $string
     * @param ExtensionInterface|class-string<ExtensionInterface> $extension
     * @return static
     */
    public static function extensionCoreCannotBeRemoved(
        string $string,
        string|ExtensionInterface $extension
    ): static {
        return new static(
            sprintf(
                // translators: 1: The operation, 2. The object name
                __('Cannot %1$s extension %2$s because it is a core extension', 'traydigita'),
                $string,
                get_class($extension)
            )
        );
    }

    /**
     * Cannot remove core extension exception
     *
     * @param string $string
     * @param AbstractAdminPage|class-string<AbstractAdminPage> $page
     * @return static
     */
    public static function adminMenuSubmenuAlreadyExists(
        string $string,
        AbstractAdminPage|string $page
    ): static {
        return new static(
            sprintf(
                // translators: 1: The operation, 2. The object name
                __('Cannot %1$s submenu %2$s because it already exists', 'traydigita'),
                $string,
                is_string($page) ? $page : get_class($page)
            )
        );
    }

    /**
     * Cannot remove core extension exception
     *
     * @param string $string
     * @param AbstractAdminPage|class-string<AbstractAdminPage> $page
     * @return static
     */
    public static function invalidAdminPage(string $string, string|AbstractAdminPage $page): static
    {
        return new static(
            sprintf(
                // translators: 1: The operation, 2. The object name
                __('Cannot %1$s admin page %2$s because it is invalid', 'traydigita'),
                $string,
                is_string($page) ? $page : get_class($page)
            )
        );
    }
}
