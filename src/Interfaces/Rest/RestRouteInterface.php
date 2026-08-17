<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Rest;

use WP_REST_Request;
use WP_REST_Response;

interface RestRouteInterface
{
    /**
     * RestRouteInterface constructor.
     *
     * @param RestInterface $rest
     */
    public function __construct(RestInterface $rest);

    /**
     * Get the route title
     *
     * @return string
     */
    public function getTitle() : string;

    /**
     * Get the rest instance
     *
     * @return RestInterface
     */
    public function getRest() : RestInterface;

    /**
     * Get the route pattern
     *
     * @return string
     */
    public function getPattern() : string;

    /**
     * Get the route methods
     *
     * @return string
     * @see \WP_REST_Server
     */
    public function getMethod() : string;

    /**
     * Get the route arguments
     *
     * @return array<string, array<string, mixed>>
     */
    public function getArgs() : array;

    /**
     * Get the route schema
     *
     * @return array<string, mixed>
     * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
     */
    public function getSchemaProperties() : array;

    /**
     * Check if the route is allowed
     * This is a permission callback for the route, it should return true if the route is allowed, false otherwise.
     *
     * @param WP_REST_Request $request
     * @return bool
     */
    public function isAllowed(WP_REST_Request $request) : bool;

    /**
     * Handle the route request
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function handle(WP_REST_Request $request) : WP_REST_Response;
}
