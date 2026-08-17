<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractExtension;

class GraphQL extends AbstractExtension
{
    protected string $name = 'GraphQL';

    protected string $version = '1.0.0';

    public function getDescription(): string
    {
        return $this->description ??= __('GraphQL extension for Headless WordPress', 'traydigita');
    }
}
