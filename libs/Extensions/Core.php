<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractCoreExtension;
use function __;
use const PHP_INT_MAX;

final class Core extends AbstractCoreExtension
{
    /**
     * @var int $priority The priority of the core extension
     */
    protected int $priority = PHP_INT_MAX;

    /**
     * @var bool $shouldBeActive Whether the core extension should be active
     */
    protected bool $shouldBeActive = true;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name ??= __('Core Extension', 'traydigita');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->description ??= __('Core extension for Headless WordPress', 'traydigita');
    }
}
