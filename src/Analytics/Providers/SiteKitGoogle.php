<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Analytics\Providers;

use Google\Site_Kit\Core\Modules\Modules;
use Google\Site_Kit\Modules\Analytics_4;
use Google\Site_Kit\Plugin;
use Google\Site_Kit_Dependencies\Google\Service\AnalyticsData\RunReportResponse;
use Throwable;
use TrayDigita\WP\Headless\Resource\Abstracts\AbstractsAnalytics;
use TrayDigita\WP\Headless\Resource\Analytics\Records\CollectionRecordsAnalytics;
use TrayDigita\WP\Headless\Resource\Analytics\Records\RecordAnalytic;
use TrayDigita\WP\Headless\Resource\Components\Options;
use WP_Error;
use function __;
use function array_keys;
use function array_values;
use function class_exists;
use function count;
use function date;
use function doing_action;
use function explode;
use function get_posts;
use function is_array;
use function is_numeric;
use function is_scalar;
use function is_string;
use function is_wp_error;
use function strtotime;
use function time;
use function trim;

class SiteKitGoogle extends AbstractsAnalytics
{
    public const OPTION_NAME = 'google_site_kit_analytics';

    public const RESULT_NUM = 15;

    private static bool $siteKitReady = false;

    private Analytics_4|false $analytics_4;

    private array $args;

    private ?WP_Error $error;

    public function __construct(public readonly Options $option)
    {
    }

    private function getAnalyticsObject(): ?Analytics_4
    {
        if (!self::isTheSiteKitReady()) {
            return null;
        }
        if (isset($this->analytics_4)) {
            return $this->analytics_4?:null;
        }
        try {
            $plugin = Plugin::instance();
            $context = $plugin->context();
            $modules = new Modules($context);
            $analytics = $modules->get_module(Analytics_4::MODULE_SLUG);
            if (!$analytics instanceof Analytics_4
                || !$analytics->is_connected()
            ) {
                $this->error = new WP_Error(
                    'not_ready',
                    __('Analytics API is not connected', 'traydigita')
                );
                $this->analytics_4 = false;
                return null;
            }
            return $this->analytics_4 = $analytics;
        } catch (Throwable) {
            return null;
        }
    }

    public function isReady(): bool
    {
        return $this->getAnalyticsObject() !== null;
    }

    private function getAnalyticsArgs(): array
    {
        return $this->args ??= [
            'metrics' => [
                ['name' => 'screenPageViews']
            ],
            'dimensions' => [
                ['name' => 'pagePath'],
            ],
            'startDate' => date('Y-m-d', strtotime('-7 days')),
            'endDate' => date('Y-m-d', strtotime('-1 day')),
            'orderby' => [
                [
                    'metric' => ['metricName' => 'screenPageViews'],
                    'desc' => true,
                ],
            ],
            //'keepEmptyRows' => true,
            'limit' => 100,
            'offset' => 0,
        ];
    }

    private static function isTheSiteKitReady(): bool
    {
        if (self::$siteKitReady) {
            return true;
        }
        if (doing_action('after_setup_theme')) {
            return false;
        }
        return self::$siteKitReady = class_exists(Plugin::class)
            && class_exists(Analytics_4::class)
            && class_exists(RunReportResponse::class)
            && class_exists(Modules::class);
    }

    private function setData(CollectionRecordsAnalytics $data): void
    {
        $this->option->setOption(self::OPTION_NAME, $data);
    }

    private CollectionRecordsAnalytics $records;

    /**
     * @return CollectionRecordsAnalytics|WP_Error
     * /
     */
    public function getRecords(): CollectionRecordsAnalytics|WP_Error
    {
        if (isset($this->records)) {
            return $this->records;
        }
        $option = $this->option->getOption(self::OPTION_NAME);
        if (!$option instanceof CollectionRecordsAnalytics) {
            $option = $this->getForceRecords();
            if (!$option instanceof CollectionRecordsAnalytics) {
                return $option;
            }
            if (!isset($this->records)) {
                $this->setData($option);
            }
            $this->records = $option;
            return $this->records;
        }
        $this->records = $option;
        if ($this->records->isExpire()) {
            $data = $this->getForceRecords();
            if ($data instanceof CollectionRecordsAnalytics) {
                $this->records = $data->mergeWith($option, self::RESULT_NUM);
            }
        }
        return $this->records;
    }

    public function getForceRecords(): WP_Error|CollectionRecordsAnalytics
    {
        if (!self::isTheSiteKitReady()) {
            return new WP_Error(
                'not_ready',
                __('Google SiteKit is not ready or not installed', 'traydigita')
            );
        }

        $analytics = $this->getAnalyticsObject();
        if (!$analytics) {
            if (!empty($this->error)) {
                return $this->error;
            }
            return new WP_Error(
                'not_ready',
                __('Google SiteKit Analytics API is not ready', 'traydigita')
            );
        }
        try {
            $settings = $analytics->get_settings()->get();
            if (!is_array($settings)) {
                return new WP_Error(
                    'not_ready',
                    __('Can not get setting for analytics', 'traydigita')
                );
            }
            $propertyId = $settings['propertyID'] ?? null;
            $propertyId = is_scalar($propertyId) ? (string)$propertyId : null;
            if (!is_string($propertyId)) {
                return new WP_Error(
                    'not_ready',
                    __('Can not get property id for analytics', 'traydigita')
                );
            }
            $response = $analytics->get_data('report', $this->getAnalyticsArgs());
            if (is_wp_error($response)) {
                return $response;
            }
            if ($response instanceof RunReportResponse) {
                $rows = $response->getRows();
                $paths = [];
                foreach ($rows as $row) {
                    $pathObj = $row->getDimensionValues()[0] ?? null;
                    $metrics = $row->getMetricValues()[0] ?? null;
                    if (!$pathObj || !$metrics) {
                        continue;
                    }
                    $path = $pathObj->getValue();
                    $metric = $metrics->getValue();
                    if (!is_string($path)) {
                        continue;
                    }
                    $path = trim($path, '/');
                    if ($path === '' || str_starts_with($path, '^page/')) {
                        continue;
                    }
                    // Get rid of the #anchor.
                    $path = explode('#', $path)[0];
                    $path = explode('?', $path)[0];
                    $metric = is_string($metric) ? trim($metric) : $metric;
                    $metric = !is_numeric($metric) ? 0 : (int)$metric;
                    $paths[$path] = $metric;
                }
                if (empty($paths)) {
                    return new CollectionRecordsAnalytics($propertyId, time());
                }
                $posts = get_posts([
                    'post_name__in' => array_keys($paths),
                    'post_type' => 'any',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                ]);
                $maps = [];
                foreach ($posts as $post) {
                    $count = $paths[$post->post_name] ?? 0;
                    $map = RecordAnalytic::fromPost($post, $count);
                    $maps[] = $map;
                }
                unset($paths, $posts);
                $maps = array_values($maps);
                $return = new CollectionRecordsAnalytics(
                    $propertyId,
                    time(),
                    ...$maps
                );
                if (count($maps) >= 15) {
                    $this->records = $return;
                    $this->setData($return);
                }
                return $return;
            }
        } catch (Throwable $e) {
            return new WP_Error('error', $e->getMessage());
        }
        return new WP_Error('error', __('Can not get records', 'traydigita'));
    }
}
