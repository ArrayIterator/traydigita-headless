<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Exceptions;

use LogicException;
use PHPStan\DependencyInjection\ExtensionInterface;
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
                'Cannot %s extension %s because it is already loaded',
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
                'Cannot %s extension %s because it is already booted',
                $operation,
                is_string($className) ? $className : get_class($className)
            )
        );
    }

    /**
     * @param string $operation
     * @return static
     */
    public static function invalidUser(
        string $operation,
    ): static {
        return new static(
            sprintf(
                'Cannot %s because the user is invalid',
                $operation
            )
        );
    }
}
