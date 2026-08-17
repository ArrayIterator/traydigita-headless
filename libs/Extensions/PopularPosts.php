<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractExtension;

class PopularPosts extends AbstractExtension
{
    protected string $version = '1.0.0';

    protected ?string $homepage = 'https://traydigita.com';

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
