<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Rest;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractRoute;
use WP_REST_Request;
use WP_REST_Response;
use function current_user_can;

class PluginInfo extends AbstractRoute
{
    public function getTitle(): string
    {
        return $this->title ??= __('Plugin Info', 'traydigita');
    }

    public function getArgs(): array
    {
        return [
            'slug' => [
                'description' => __('Plugin slug', 'traydigita'),
                'type' => 'string',
                'minLength' => 1,
                'required' => true,
            ],
        ];
    }

    public function getSchemaProperties(): array
    {
        return $this->schema ??= [
            'slug' => [
                'description' => __('Plugin slug', 'traydigita'),
                'type' => 'string',
                'context' => ['view', 'edit'],
                'readonly' => true,
            ],
            'version' => [
                'description' => __('Plugin version', 'traydigita'),
                'type' => 'string',
                'context' => ['view', 'edit'],
                'readonly' => true,
            ],
            'name' => [
                'description' => __('Plugin name', 'traydigita'),
                'type' => 'string',
                'context' => ['view', 'edit'],
                'readonly' => true,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function isAllowed(WP_REST_Request $request): bool
    {
        return current_user_can('install_plugins');
    }

    /**
     * @inheritdoc
     */
    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'slug' => $request->get_param('slug') ?? '',
        ]);
    }
}
