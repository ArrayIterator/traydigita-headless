<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

// todo: implement the rest api component
use TrayDigita\WP\Headless\Resource\Abstracts\AbstractRoute;
use TrayDigita\WP\Headless\Resource\Interfaces\Rest\RestInterface;
use TrayDigita\WP\Headless\Resource\Utils\Callback;
use function class_exists;
use function did_action;
use function get_class;
use function is_object;
use function is_subclass_of;
use function ltrim;
use function strtolower;

/**
 * @template TRoute of AbstractRoute
 */
class Rest implements RestInterface
{
    /**
     * @var string $namespace The namespace for the REST API
     */
    public readonly string $namespace;

    /**
     * @var array<string, TRoute> $routes The registered REST routes
     */
    private array $routes;

    /**
     * @var bool $initialized Whether the REST API has been initialized
     */
    private bool $initialized = false;

    /**
     * Rest constructor.
     *
     * @param Container $container The container instance
     */
    public function __construct(public readonly Container $container)
    {
        $this->namespace = 'traydigita-headless/v1';
    }

    /**
     * Initialize the REST API
     * Dispatch the rest_api_init hook if it hasn't been dispatched yet
     */
    public function dispatchHook(): void
    {
        if ($this->initialized) {
            return;
        }
        if (!did_action('rest_api_init') && !did_action('rest_api_init')) {
            return;
        }
        $this->initialized = true;
        // todo: register the routes
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
     * Get the container instance
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the namespace for the REST API
     *
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * Register a REST class
     *
     * @param class-string<TRoute> $route
     * @return ?TRoute
     */
    public function register(string $route): ?AbstractRoute
    {
        if (!class_exists($route) || !is_subclass_of($route, AbstractRoute::class)) {
            return null;
        }
        $routeClassName = $this->makeLowercase($route);
        if (isset($this->routes[$routeClassName])) {
            return $this->routes[$routeClassName];
        }
        return Callback::apply(function ($route, $routeClassName) {
            $routeInstance = new $route($this);
            if (!$routeInstance instanceof AbstractRoute) {
                return null;
            }
            $this->routes[$routeClassName] = $routeInstance;
            return $routeInstance;
        }, $route, $routeClassName);
    }
}
