<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Enums;

enum PluginType: string
{
    case EXTERNAL = 'external';
    case INTERNAL = 'bundled';
    case WORDPRESS = 'wordpress';
}
