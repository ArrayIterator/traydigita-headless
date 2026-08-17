<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractCoreExtension;
use function __;

final class PopularPosts extends AbstractCoreExtension
{
    /**
     * @var int $priority The priority of the core extension
     */
    protected int $priority = 25;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name ??= __('Popular Posts', 'traydigita');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->description ??= __('Popular Posts extension for Headless WordPress', 'traydigita');
    }
}
