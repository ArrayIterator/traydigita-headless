<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Abstracts;

use TrayDigita\WP\Headless\Resource\Interfaces\Rest\RestInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Rest\RestRouteInterface;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

abstract class AbstractRoute implements RestRouteInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $schema;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $args;

    /**
     * @var string
     */
    protected string $pattern;

    /**
     * @var string
     */
    protected string $method;

    /**
     * @var string
     */
    protected string $title;

    /**
     * AbstractRoute constructor.
     *
     * @param RestInterface $rest
     */
    final public function __construct(public readonly RestInterface $rest)
    {
    }

    /**
     * @inheritdoc
     */
    public function getRest(): RestInterface
    {
        return $this->rest;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getPattern(): string
    {
        return $this->pattern ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getMethod(): string
    {
        return $this->method ?? WP_REST_Server::READABLE;
    }

    /**
     * @inheritdoc
     */
    public function getArgs(): array
    {
        return $this->args ?? [];
    }

    /**
     * @inheritdoc
     */
    public function getSchemaProperties(): array
    {
        return $this->schema ?? [];
    }

    /**
     * @inheritdoc
     */
    abstract public function isAllowed(WP_REST_Request $request): bool;

    /**
     * @inheritdoc
     */
    abstract public function handle(WP_REST_Request $request): WP_REST_Response;
}
