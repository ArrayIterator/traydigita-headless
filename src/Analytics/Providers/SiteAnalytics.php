<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Analytics\Providers;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractsAnalytics;
use TrayDigita\WP\Headless\Resource\Analytics\Records\CollectionRecordsAnalytics;
use TrayDigita\WP\Headless\Resource\Analytics\Records\RecordAnalytic;
use TrayDigita\WP\Headless\Resource\Components\Database;
use TrayDigita\WP\Headless\Resource\Components\Options;
use WP_Error;
use WP_Post;
use function array_all;
use function array_keys;
use function array_values;
use function date;
use function get_post;
use function get_posts;
use function is_array;
use function is_int;
use function min;
use function time;

class SiteAnalytics extends AbstractsAnalytics
{
    public const TABLE_NAME = 'traydigita_posts_views_counter';

    public const OPTION_NAME = 'site_analytics';

    public const CACHE_TIME = 300;

    public const MAX_LIMIT = 200;

    private CollectionRecordsAnalytics $records;

    private string $tableName;

    public function __construct(
        public readonly Database $database,
        public readonly Options $option
    ) {
    }

    public function isReady(): bool
    {
        return true;
    }

    public function getTableName(): string
    {
        return $this->tableName ??= $this->database->quoteIdentifier(self::TABLE_NAME);
    }

    public function addView(int|WP_Post $post): void
    {
        self::createTable();
        $post = get_post($post);
        if (!$post) {
            return;
        }
        if ($post->post_status !== 'publish') {
            return;
        }
        $view_date = date('Y-m-d');
        $post_id = $post->ID;
        $table_name = $this->getTableName();
        $this->database->executeQuery(
            "INSERT INTO $table_name (`post_id`, `view_date`, `view_count`)
            VALUES (%d, %s, 1) ON DUPLICATE KEY UPDATE `view_count` = `view_count` + 1",
            $post_id,
            $view_date
        );
    }

    /**
     * @param int $days
     * @param int $limit
     * @param bool $force
     * @return array<int, int>
     */
    public function getPopularPostIdsFromDaysInterval(
        int $days = 30,
        int $limit = 30,
        bool $force = false
    ): array {
        if ($days < 1) {
            return [];
        }
        $limit = min($limit, self::MAX_LIMIT);
        if ($limit < 1) {
            return [];
        }
        $cache_key = 'traydigita_popular_posts_[' . $days . '_' . $limit . ']';
        $cached_data = $this->option->getTransient($cache_key);
        if (!$force
            && is_array($cached_data)
            && array_all($cached_data, static fn($k, $v) => is_int($k) && is_int($v))
        ) {
            return $cached_data;
        }
        self::createTable();
        $table_name = $this->getTableName();
        $table_post = $this->database->getTablePosts();
        $results = $this->database->getResults(
            "SELECT post_id, SUM(view_count) as total_views
                        FROM $table_name as popular
                        INNER JOIN $table_post as post
                            ON (
                                post.ID = popular.post_id
                                AND post.post_type = 'post'
                                AND post.post_status = 'publish'
                            )
                       WHERE
                           view_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
                        GROUP BY post_id
                        ORDER BY total_views DESC LIMIT %d",
            false,
            $days,
            $limit
        );
        if (!$results) {
            if (!$this->database->getLastError()) {
                $this->option->setTransient($cache_key, [], self::CACHE_TIME);
            }
            return [];
        }
        $post_ids = [];
        foreach ($results as $result) {
            $post_ids[$result->post_id] = $result->total_views;
        }
        $this->option->setTransient($cache_key, $post_ids, self::CACHE_TIME);
        return $post_ids;
    }

    /**
     * @param int $days
     * @param int $limit
     * @param bool $force
     * @return array<int, array{post: WP_Post, count: int}>
     */
    public function getPopularPostsFromDaysInterval(
        int $days = 30,
        int $limit = 30,
        bool $force = false
    ): array {
        self::createTable();
        $post_ids = $this->getPopularPostIdsFromDaysInterval($days, $limit, $force);
        $posts = [];
        foreach (get_posts([
            'include' => array_keys($post_ids),
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ]) as $post) {
            $posts[$post->ID] = [
                'post' => $post,
                'count' => (int)($post_ids[$post->ID] ?? 0)
            ];
        }
        return $posts;
    }

    private static bool $table_created = false;

    private static function createTable(): void
    {
        if (self::$table_created) {
            return;
        }
        self::$table_created = true;
        $table_name = self::table();
        if (!Database::hasTable($table_name)) {
            $charset_collate = Database::charsetCollate();
            $sql = "
CREATE TABLE IF NOT EXISTS $table_name (
    `post_id` bigint(20) NOT NULL,
    `view_date` date NOT NULL,
    `view_count` int(11) DEFAULT 0 NOT NULL,
    PRIMARY KEY (`post_id`, `view_date`),
    INDEX `idx_date_count` (`view_date`, `view_count`)
) $charset_collate;";
            Database::execute($sql);
        }
    }

    public function getRecords(): CollectionRecordsAnalytics|WP_Error
    {
        if (isset($this->records)) {
            return $this->records;
        }
        $records = Options::getOption(self::OPTION_NAME);
        if (!$records instanceof CollectionRecordsAnalytics || $records->isExpire(1)) {
            $posts = $this->getPopularPostsFromDaysInterval(7, 100, true);
            $maps = [];
            foreach ($posts as $data) {
                ['post' => $post, 'count' => $count] = $data;
                $map = RecordAnalytic::fromPost($post, $count);
                $maps[] = $map;
            }
            unset($paths, $posts);
            $maps = array_values($maps);
            $records = new CollectionRecordsAnalytics(
                'local',
                time(),
                ...$maps
            );
            Options::setOption(self::OPTION_NAME, $records);
        }
        $this->records = $records;
        return $this->records;
    }
}
