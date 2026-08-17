<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Exceptions;

use LogicException;
use TrayDigita\WP\Headless\Resource\Interfaces\DatabaseExceptionInterface;

class InvalidLogicExceptionInterface extends LogicException implements DatabaseExceptionInterface
{
}
