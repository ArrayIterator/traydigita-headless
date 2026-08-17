<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Rest;

use TrayDigita\WP\Headless\Resource\Components\Container;

/**
 * @template TRoute of RestRouteInterface
 */
interface RestInterface
{
    /**
     * RestInterface constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container);

    /**
     * Get the container instance
     *
     * @return Container
     */
    public function getContainer(): Container;

    /**
     * Get the namespace for the REST API
     *
     * @return string
     */
    public function getNamespace() : string;

    /**
     * Register a REST class
     *
     * @param class-string<TRoute> $route
     */
    public function register(string $route);
}
