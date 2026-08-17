<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractCoreExtension;
use function __;

final class TokenizerAuth extends AbstractCoreExtension
{
    /**
     * @var int $priority The priority of the core extension
     */
    protected int $priority = 500;

    /**
     * @var bool $shouldBeActive Whether the core extension should be active
     */
    protected bool $shouldBeActive = true;

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name ??= __('Tokenizer Auth', 'traydigita');
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->description ??= __('Tokenizer Auth extension for Headless WordPress', 'traydigita');
    }
}
