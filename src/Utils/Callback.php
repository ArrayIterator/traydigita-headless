<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Utils;

use Throwable;
use TrayDigita\WP\Headless\Resource\Exceptions\CaughtException;
use function restore_error_handler;
use function set_error_handler;

class Callback
{
    /**
     * Suppress errors and exceptions from a callback
     *
     * @template TResult of mixed
     * @template TArgs of mixed
     * @param callable(TArgs...): TResult $callback
     * @param Throwable|null $error
     * @param TArgs ...$args
     * @return TResult|null
     */
    public static function suppress(
        callable $callback,
        Throwable &$error = null,
        mixed ...$args
    ) : mixed {
        $error = null;
        try {
            set_error_handler(function (int $errNo, string $errorStr, string $errFile, int $errLine) use (&$error) {
                $error = new CaughtException($errorStr, 0, $errNo, $errFile, $errLine);
            });
            return $callback(...$args);
        } catch (Throwable $e) {
            $error = $e;
            return null;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Suppress errors and exceptions from a callback
     *
     * @template TResult of mixed
     * @template TArgs of mixed
     * @param callable(TArgs...): TResult $callback
     * @param TArgs ...$args
     * @return TResult|null
     */
    public static function apply(
        callable $callback,
        mixed ...$args
    ) : mixed {
        return self::suppress($callback, $error, ...$args);
    }
}
