<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractCoreExtension;
use function __;

final class GraphQL extends AbstractCoreExtension
{
    /**
     * @inheritdoc
     */
    protected int $priority = 50;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name ??= __('GraphQL Integration', 'traydigita');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->description ??= __('GraphQL extension for WP GraphQL plugin support', 'traydigita');
    }
}
