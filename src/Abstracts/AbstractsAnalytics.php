<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Abstracts;

use TrayDigita\WP\Headless\Resource\Analytics\Records\CollectionRecordsAnalytics;
use WP_Error;

abstract class AbstractsAnalytics
{
    /**
     * Get the analytics records.
     *
     * @return CollectionRecordsAnalytics|WP_Error
     */
    abstract public function getRecords(): CollectionRecordsAnalytics|WP_Error;

    /**
     * Indicates whether the analytics provider is ready to use.
     *
     * @return bool
     */
    abstract public function isReady(): bool;
}
