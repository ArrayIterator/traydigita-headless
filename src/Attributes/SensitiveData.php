<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY
    | Attribute::TARGET_METHOD
    | Attribute::TARGET_CLASS
    | Attribute::TARGET_PARAMETER
    | Attribute::TARGET_FUNCTION
    | Attribute::TARGET_ALL)]
class SensitiveData
{
    /**
     * @param string $reason
     */
    public function __construct(
        public readonly string $reason = 'Sensitive Data'
    ) {
    }
}
