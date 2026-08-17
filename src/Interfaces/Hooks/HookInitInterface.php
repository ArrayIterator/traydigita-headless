<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Hooks;

interface HookInitInterface
{
    /**
     * Hook called by
     * add_action('init', [$object, 'initHook']);
     *
     * @return void
     */
    public function initHook() : void;
}
