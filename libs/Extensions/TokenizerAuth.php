<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Extensions;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractExtension;
use function __;

class TokenizerAuth extends AbstractExtension
{
    protected string $name = 'Tokenizer Auth';

    protected string $version = '1.0.0';

    public function getDescription(): string
    {
        return $this->description ??= __('Tokenizer Auth extension for Headless WordPress', 'traydigita');
    }
}
