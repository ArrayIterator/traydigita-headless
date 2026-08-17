<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Hooks;

interface HookAdminEnqueueScriptsInterface
{
    /**
     * Hook called by
     * add_action('admin_enqueue_scripts', [$object, 'adminEnqueueScriptHook']);
     *
     * @return void
     */
    public function adminEnqueueScriptHook() : void;
}
