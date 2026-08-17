<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Exceptions;

use InvalidArgumentException as Core;
use TrayDigita\WP\Headless\Resource\Interfaces\DatabaseExceptionInterface;

class InvalidArgumentExceptionInterface extends Core implements DatabaseExceptionInterface
{
}
