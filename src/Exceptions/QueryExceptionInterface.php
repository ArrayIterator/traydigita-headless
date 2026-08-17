<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Exceptions;

use RuntimeException;
use TrayDigita\WP\Headless\Resource\Interfaces\DatabaseExceptionInterface;

class QueryExceptionInterface extends RuntimeException implements DatabaseExceptionInterface
{
}
