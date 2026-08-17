<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Exceptions;

use ErrorException;
use TrayDigita\WP\Headless\Resource\Interfaces\TrayDigitaExceptionInterface;

class CaughtException extends ErrorException implements TrayDigitaExceptionInterface
{
}
