<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Interfaces\OptionInterface;
use TrayDigita\WP\Headless\Resource\Traits\OptionTrait;
use function sprintf;

final class SiteOptions implements OptionInterface
{
    use OptionTrait;

    /**
     * The name of the option in the database.
     * @var string
     */
    public const OPTION_NAME = 'site_options';

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return self::TYPE_SITE_OPTION;
    }

    /**
     * @inheritdoc
     */
    public function getOptionName(): string
    {
        return sprintf('%s%s', self::PREFIX, self::OPTION_NAME);
    }
}
