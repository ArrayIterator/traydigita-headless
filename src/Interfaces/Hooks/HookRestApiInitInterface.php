<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Hooks;

interface HookRestApiInitInterface
{
    /**
     * Hook called by
     * add_action('rest_api_init', [$object, 'initHook']);
     * Initialize the REST API
     * Dispatch the rest_api_init hook if it hasn't been dispatched yet
     * @return void
     */
    public function initHook() : void;
}
