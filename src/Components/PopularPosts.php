<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Analytics\Providers\SiteAnalytics;
use TrayDigita\WP\Headless\Resource\Analytics\Providers\SiteKitGoogle;
use TrayDigita\WP\Headless\Resource\Analytics\Records\CollectionRecordsAnalytics;
use TrayDigita\WP\Headless\Resource\Analytics\Records\RecordAnalytic;
use function count;
use function get_posts;
use function time;

final class PopularPosts
{
    private CollectionRecordsAnalytics $records;

    public function __construct(
        public readonly SiteKitGoogle $siteKit,
        public readonly SiteAnalytics $siteAnalytics
    ) {
    }

    public function getPopularPosts()
    {
        if (isset($this->records)) {
            return $this->records;
        }
        if ($this->siteKit->isReady()
            && ($records = $this->siteKit->getRecords()) instanceof CollectionRecordsAnalytics
        ) {
            if (count($records) >= 15) {
                return $this->records = $records;
            }
            $additional = $this->siteAnalytics->getRecords();
            if ($additional instanceof CollectionRecordsAnalytics) {
                $records = $records->mergeWith($additional);
            }
        } else {
            $records = $this->siteAnalytics->getRecords();
            if (!$records instanceof CollectionRecordsAnalytics) {
                $records = new CollectionRecordsAnalytics('local', time());
            }
        }
        if (count($records) >= 15) {
            return $this->records = $records;
        }
        $post_ids = [];
        foreach ($records as $rec) {
            $post_ids[] = $rec->id;
        }
        $count = 15 - count($post_ids);
        if ($count <= 0) {
            return $this->records;
        }
        $maps = [];
        foreach (get_posts([
            'exclude' => $post_ids,
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => $count,
        ]) as $post) {
            $maps[] = RecordAnalytic::fromPost($post, 0);
        }
        $records = $records->mergeWith(
            new CollectionRecordsAnalytics('local', time(), ...$maps)
        );
        return $this->records = $records;
    }
}
