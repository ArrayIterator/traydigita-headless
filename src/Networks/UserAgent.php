<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Networks;

use Psr\Http\Message\ServerRequestInterface;
use function array_merge;
use function implode;
use function preg_match;
use function preg_quote;
use function sprintf;
use function str_starts_with;
use function strlen;
use function trim;

final class UserAgent
{
    public const TYPE_HUMAN = 'human';

    public const TYPE_SPEED = 'speed';

    public const TYPE_AUDIT = 'audit';

    public const TYPE_BOT = 'bot';

    public const TYPE_UNKNOWN = 'unknown';

    public readonly string $userAgent;

    private bool $isAuditBot;

    private bool $isSpeedBot;

    private bool $isCommonBot;

    private bool $isMaybeHuman;

    /**
     * @var ClientBrowserType
     */
    public readonly ClientBrowserType $clientBrowserType;

    /**
     * @param ServerRequestInterface $request
     */
    public function __construct(public readonly ServerRequestInterface $request)
    {
        $userAgent = $this->request->getHeaderLine('User-Agent');
        $this->userAgent = $userAgent;
        $this->clientBrowserType = new ClientBrowserType($this);
    }

    /**
     * Get the type of user agent based on the request.
     * @return self::TYPE_HUMAN|self::TYPE_SPEED|self::TYPE_AUDIT|self::TYPE_BOT|self::TYPE_UNKNOWN
     */
    public function getType(): string
    {
        if ($this->isMaybeHumanBrowser()) {
            return self::TYPE_HUMAN;
        }
        if ($this->isAuditBot()) {
            return self::TYPE_AUDIT;
        }
        if ($this->isSpeedCheckerBot()) {
            return self::TYPE_SPEED;
        }
        if ($this->isCommonBot()) {
            return self::TYPE_BOT;
        }
        return self::TYPE_UNKNOWN;
    }

    /**
     * @param ServerRequestInterface $request
     * @return static
     */
    public function withRequest(ServerRequestInterface $request): self
    {
        if ($request === $this->request) {
            return $this; // reuse the same instance if the request is the same
        }
        return new self($request);
    }

    public function isBot(): bool
    {
        return $this->isCommonBot() || $this->isSpeedCheckerBot() || $this->isAuditBot();
    }

    public function isCommonBot(): bool
    {
        return $this->isCommonBot ??= (bool)preg_match(self::commonBotRegexP(), $this->userAgent);
    }

    public function isAuditBot(): bool
    {
        return $this->isAuditBot ??= (bool)preg_match(self::auditRegexP(), $this->userAgent);
    }

    public function isSpeedCheckerBot(): bool
    {
        return $this->isSpeedBot ??= (bool)preg_match(self::speedCheckerRegexP(), $this->userAgent);
    }

    public function isMaybeHumanBrowser(): bool
    {
        if (isset($this->isMaybeHuman)) {
            return $this->isMaybeHuman;
        }
        $this->isMaybeHuman = false;
        $userAgent = $this->userAgent;
        $prefix = 'Mozilla/5.0 ';
        $minOsLength = 10;
        $minAttributes = 5;
        $minLength = strlen($prefix) + $minAttributes + $minOsLength;
        if (strlen($userAgent) < $minLength || !str_starts_with($userAgent, $prefix)) {
            return false;
        }
        if (!preg_match(
            '~^Mozilla/5\.0\s+\((?P<os>[^)\n\r\t\f\v\0]+)\)(?P<attributes>[^\n\r\t\f\v\0]+)$~',
            $userAgent,
            $match
        )) {
            return false;
        }
        $attributes = $match['attributes'];
        $os = trim($match['os']);
        if (strlen($os) < $minOsLength || strlen($attributes) < $minAttributes || str_starts_with($os, ';')) {
            return false;
        }
        $regex = <<<'REGEXP'
~
windows\s+nt|windows\s+phone|arm;
|macintosh|mac\s+os(\s+x)?|iphone|ipad|ipod|watch
|xros|android|harmonyos|huawei|kindle
|linux; u;|linux|ubuntu|debian|fedora|red hat|suse|freebsd|openbsd|netbsd|sunos|cros|crkey
|googletv|appletv|tizen|webos|rokubrowser|firetv|smart-tv|hbbtv
|playstation|nintendo|xbox|wear os|oculus|vive
~xi
REGEXP;
        if (!preg_match($regex, $os)) {
            return false;
        }
        return $this->isMaybeHuman = !$this->isBot();
    }

    private static string $auditRegex;

    private static string $speedCheckerRegex;

    private static string $commonBotRegexP;

    private static string $botsRegexP;

    public static function auditRegexP(): string
    {
        return self::$auditRegex ??= sprintf(
            '~(%s)~',
            implode('|', array_map(static fn($e) => preg_quote($e, '~'), self::AUDIT_LISTS))
        );
    }

    public static function speedCheckerRegexP(): string
    {
        return self::$speedCheckerRegex ??= sprintf(
            '~(%s)~',
            implode('|', array_map(static fn($e) => preg_quote($e, '~'), self::SPEED_CHECKER_LISTS))
        );
    }

    public static function commonBotRegexP(): string
    {
        return self::$commonBotRegexP ??= sprintf(
            '~(%s|%s)~',
            self::SPECIAL_BOT_REGEX,
            implode('|', array_map(static fn($e) => preg_quote($e, '~'), self::COMMON_BOT_LISTS))
        );
    }

    public static function botRegexP(): string
    {
        if (isset(self::$botsRegexP)) {
            return self::$botsRegexP;
        }
        $bots = array_merge(
            self::AUDIT_LISTS,
            self::SPEED_CHECKER_LISTS,
            self::COMMON_BOT_LISTS,
        );
        return self::$botsRegexP ??= sprintf(
            '~(%s|%s)~',
            self::SPECIAL_BOT_REGEX,
            implode(
                '|',
                array_map(
                    static fn($e) => preg_quote($e, '~'),
                    $bots
                )
            )
        );
    }

    public const SPECIAL_BOT_REGEX = '(?:[a-z0-9\-]*bot(?:[\/;)\s-]|$))';

    /**
     * @var string[]
     */
    public const AUDIT_LISTS = [
        'AhrefsBot',
        'SemrushBot',
        'DotBot',
        'Rogerbot',
        'Screaming Frog',
        'SitecheckerBot',
        'SEOSiteCheckup',
        'MegaIndex',
        'Crawlera',
        'Mozlila',
        'MJ12bot',
        'SerpstatBot',
        'SpyFu',
        'CognitiveSEO',
        'RavenBot',
        'WooRank',
        'Lipperhey',
        'SiteExplorer',
        'Seomoz'
    ];

    /**
     * @var string[]
     */
    public const SPEED_CHECKER_LISTS = [
        'Chrome-Lighthouse',
        'GTmetrix',
        'PTST',
        'WebPageTest',
        'Pingdom',
        'DebugBear',
        'SpeedCurve',
        'Dareboost',
        'Catchpoint',
        'k6',
        'JMeter',
        'WebPerf',
        'Lighthouse',
        'StatusCake',
        'Site24x7',
        'UptimeRobot',
        'Calibre',
        'Boomerang',
        'GomezAgent',
        'Nodeping'
    ];

    /**
     * @var string[]
     */
    public const COMMON_BOT_LISTS = [
        // AI & LLM Scraping Bots (Model Training & RAG Fetching)
        'ChatGPT-User',
        'GPTBot',
        'ClaudeBot',
        'Claude-Web',
        'PerplexityBot',
        'OAI-SearchBot',
        'Meta-ExternalAgent',
        'Meta-ExternalFetch',
        'Gemini',
        'cohere-ai',
        'Amazonbot',
        'Applebot-Extended',
        'Anthropic-AI',

        // Social Media Link Previews & Chat Apps
        'facebookexternalhit',
        'Twitterbot',
        'LinkedInBot',
        'Pinterestbot',
        'Slackbot',
        'WhatsApp',
        'TelegramBot',
        'Discordbot',
        'SkypeSpaces',
        'Embedly',
        'Viber',
        'LineBot',

        // Search Engine Crawlers (Global & Regional)
        'Googlebot',
        'Google-PageSpeed',
        'Google-Favicon',
        'Google-Read-Aloud',
        'Google-Extended',
        'Bingbot',
        'BingPreview',
        'YandexBot',
        'Baiduspider',
        'DuckDuckBot',
        'Yahoo! Slurp',
        'Sogou',
        'Exabot',
        '360Spider',
        'Qwantify',
        'BraveBot',
        'Applebot',

        // Developer Libraries, HTTP Clients & Web Scrapers
        'Wget',
        'curl',
        'Python-urllib',
        'Requests',
        'GuzzleHttp',
        'Go-http-client',
        'Node-fetch',
        'axios',
        'Scrapy',

        // Browser Automation & Headless Testing Frameworks
        'Puppeteer',
        'Playwright',
        'HeadlessChrome',
        'Selenium',
        'WebDriver',
        'PhantomJS',
        'HTTPBannerScraper'
    ];
}
