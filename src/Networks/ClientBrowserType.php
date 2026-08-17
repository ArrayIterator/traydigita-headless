<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Networks;

use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;

class ClientBrowserType
{
    // Chromium / Desktop Browsers
    public const CLIENT_CHROME = 'chrome';

    public const CLIENT_CHROMIUM = 'chromium';

    public const CLIENT_EDGE = 'edge';

    public const CLIENT_OPERA = 'opera';

    public const CLIENT_BRAVE = 'brave';

    public const CLIENT_VIVALDI = 'vivaldi';

    public const CLIENT_YANDEX = 'yandex';

    // Firefox Family
    public const CLIENT_FIREFOX = 'firefox';

    public const CLIENT_SEAMONKEY = 'seamonkey';

    public const CLIENT_PALEMOON = 'palemoon';

    public const CLIENT_WATERFOX = 'waterfox';

    // Apple
    public const CLIENT_SAFARI = 'safari';

    // Microsoft
    public const CLIENT_IE = 'ie';

    // Android / Mobile Browsers
    public const CLIENT_ANDROID = 'android';

    public const CLIENT_ANDROID_WEBVIEW = 'android_webview';

    public const CLIENT_SAMSUNG = 'samsung';

    public const CLIENT_UC = 'uc';

    public const CLIENT_OPERA_MINI = 'opera_mini';

    public const CLIENT_OPERA_MOBILE = 'opera_mobile';

    public const CLIENT_YANDEX_MOBILE = 'yandex_mobile';

    // Other Browsers
    public const CLIENT_MIDORI = 'midori';

    public const CLIENT_EPIPHANY = 'epiphany';

    public const CLIENT_GNOME_WEB = 'gnome_web';

    public const CLIENT_KONQUEROR = 'konqueror';

    public const CLIENT_MAXTHON = 'maxthon';

    public const CLIENT_AVANT = 'avant';

    public const CLIENT_SLIMJET = 'slimjet';

    public const CLIENT_COMODO = 'comodo';

    public const CLIENT_AVAST = 'avast';

    public const CLIENT_PUFFIN = 'puffin';

    public const CLIENT_DOLPHIN = 'dolphin';

    public const CLIENT_QQ = 'qq';

    public const CLIENT_BAIDU = 'baidu';

    public const CLIENT_SOGOU = 'sogou';

    public const CLIENT_360 = '360';

    // Console / Embedded Browsers
    public const CLIENT_PLAYSTATION = 'playstation';

    public const CLIENT_XBOX = 'xbox';

    public const CLIENT_NINTENDO = 'nintendo';

    // Bots / Crawlers
    public const CLIENT_GOOGLEBOT = 'googlebot';

    public const CLIENT_BINGBOT = 'bingbot';

    public const CLIENT_DUCKDUCKBOT = 'duckduckbot';

    public const CLIENT_YANDEXBOT = 'yandexbot';

    public const CLIENT_BAIDUSPIDER = 'baiduspider';

    public const CLIENT_SOGOU_SPIDER = 'sogouspider';

    public const CLIENT_EXABOT = 'exabot';

    public const CLIENT_APPLEBOT = 'applebot';

    public const CLIENT_FACEBOOKBOT = 'facebookbot';

    public const CLIENT_TWITTERBOT = 'twitterbot';

    public const CLIENT_LINKEDINBOT = 'linkedinbot';

    public const CLIENT_PINTERESTBOT = 'pinterestbot';

    public const CLIENT_SEMRUSHBOT = 'semrushbot';

    public const CLIENT_AHREFSBOT = 'ahrefsbot';

    public const CLIENT_MJ12BOT = 'mj12bot';

    public const CLIENT_DOTBOT = 'dotbot';

    public const CLIENT_PETALBOT = 'petalbot';

    // AI / LLM Crawlers
    public const CLIENT_GPTBOT = 'gptbot';

    public const CLIENT_CHATGPT = 'chatgpt';

    public const CLIENT_OAI_SEARCHBOT = 'oai_searchbot';

    public const CLIENT_CLAUDEBOT = 'claudebot';

    public const CLIENT_PERPLEXITYBOT = 'perplexitybot';

    public const CLIENT_GEMINIBOT = 'gemini';

    public const CLIENT_BYTESPIDER = 'bytespider';

    public const CLIENT_META_EXTERNAL_AGENT = 'meta_external_agent';

    public const CLIENT_AMAZONBOT = 'amazonbot';

    public const CLIENT_COHERE = 'cohere';

    // HTTP Clients
    public const CLIENT_CURL = 'curl';

    public const CLIENT_WGET = 'wget';

    public const CLIENT_HTTPIE = 'httpie';

    public const CLIENT_POSTMAN = 'postman';

    public const CLIENT_PYTHON = 'python';

    public const CLIENT_REQUESTS = 'requests';

    public const CLIENT_HTTPX = 'httpx';

    public const CLIENT_AIOHTTP = 'aiohttp';

    public const CLIENT_PHP = 'php';

    public const CLIENT_GUZZLE = 'guzzle';

    public const CLIENT_GO = 'go';

    public const CLIENT_RUST = 'rust';

    public const CLIENT_REQWEST = 'reqwest';

    public const CLIENT_JAVA = 'java';

    public const CLIENT_OKHTTP = 'okhttp';

    public const CLIENT_APACHE_HTTPCLIENT = 'apache_httpclient';

    public const CLIENT_NODE = 'node';

    public const CLIENT_NODE_FETCH = 'node_fetch';

    public const CLIENT_AXIOS = 'axios';

    public const CLIENT_UNDICI = 'undici';

    public const CLIENT_LIBWWW_PERL = 'libwww_perl';

    // Automation / Headless
    public const CLIENT_PUPPETEER = 'puppeteer';

    public const CLIENT_PLAYWRIGHT = 'playwright';

    public const CLIENT_SELENIUM = 'selenium';

    public const CLIENT_WEBDRIVER = 'webdriver';

    public const CLIENT_HEADLESS_CHROME = 'headless_chrome';

    public const CLIENT_PHANTOMJS = 'phantomjs';

    // Miscellaneous
    public const CLIENT_BOT = '(bot)';

    public const CLIENT_UNKNOWN = 'unknown';
    private string $clientBrowserType;

    private bool $isMobile;

    public function __construct(
        public readonly UserAgent $userAgent
    ) {
    }

    /**
     * Check whether the client appears to be a mobile device.
     */
    public function isMobile(): bool
    {
        return $this->isMobile ??= (bool)preg_match(
            '~(?:Mobile|Android|iPhone|iPad|iPod|Windows Phone|IEMobile|'
            . 'Opera Mini|Opera Mobi|BlackBerry|webOS|'
            . 'Silk/|Kindle|PlayBook)~i',
            $this->userAgent->userAgent
        );
    }

    /**
     * Get the detected browser/client type.
     */
    public function getBrowserType(): string
    {
        if (isset($this->clientBrowserType)) {
            return $this->clientBrowserType;
        }

        $ua = strtolower($this->userAgent->userAgent);
        $browser = match (true) {
            /*
             * Automation / Headless
             */
            str_contains($ua, 'puppeteer') =>
            self::CLIENT_PUPPETEER,

            str_contains($ua, 'playwright') =>
            self::CLIENT_PLAYWRIGHT,

            str_contains($ua, 'phantomjs') =>
            self::CLIENT_PHANTOMJS,

            str_contains($ua, 'headlesschrome') ||
            str_contains($ua, 'headless-chromium') =>
            self::CLIENT_HEADLESS_CHROME,

            str_contains($ua, 'selenium') =>
            self::CLIENT_SELENIUM,

            str_contains($ua, 'webdriver') =>
            self::CLIENT_WEBDRIVER,

            /*
             * HTTP clients
             */
            str_contains($ua, 'curl/') ||
            $ua === 'curl' =>
            self::CLIENT_CURL,

            str_contains($ua, 'wget/') ||
            $ua === 'wget' =>
            self::CLIENT_WGET,

            str_contains($ua, 'httpie') =>
            self::CLIENT_HTTPIE,

            str_contains($ua, 'postmanruntime') =>
            self::CLIENT_POSTMAN,

            str_contains($ua, 'python-requests') =>
            self::CLIENT_REQUESTS,

            str_contains($ua, 'python-httpx') ||
            str_contains($ua, 'httpx/') =>
            self::CLIENT_HTTPX,

            str_contains($ua, 'aiohttp') =>
            self::CLIENT_AIOHTTP,

            str_contains($ua, 'python-urllib') ||
            str_starts_with($ua, 'python/') =>
            self::CLIENT_PYTHON,

            str_contains($ua, 'guzzlehttp') ||
            str_contains($ua, 'guzzle/') =>
            self::CLIENT_GUZZLE,

            str_contains($ua, 'php/') =>
            self::CLIENT_PHP,

            str_contains($ua, 'go-http-client') =>
            self::CLIENT_GO,

            str_contains($ua, 'reqwest') =>
            self::CLIENT_REQWEST,

            str_contains($ua, 'rust/') =>
            self::CLIENT_RUST,

            str_contains($ua, 'okhttp') =>
            self::CLIENT_OKHTTP,

            str_contains($ua, 'apache-httpclient') =>
            self::CLIENT_APACHE_HTTPCLIENT,

            str_contains($ua, 'node-fetch') =>
            self::CLIENT_NODE_FETCH,

            str_contains($ua, 'undici') =>
            self::CLIENT_UNDICI,

            str_contains($ua, 'axios') =>
            self::CLIENT_AXIOS,

            str_contains($ua, 'node.js') ||
            str_contains($ua, 'node/') =>
            self::CLIENT_NODE,

            str_contains($ua, 'java-http-client') ||
            str_contains($ua, 'java/') =>
            self::CLIENT_JAVA,

            str_contains($ua, 'libwww-perl') =>
            self::CLIENT_LIBWWW_PERL,

            /*
             * AI / LLM
             */
            str_contains($ua, 'oai-searchbot') =>
            self::CLIENT_OAI_SEARCHBOT,

            str_contains($ua, 'chatgpt-user') =>
            self::CLIENT_CHATGPT,

            str_contains($ua, 'gptbot') =>
            self::CLIENT_GPTBOT,

            str_contains($ua, 'claudebot') ||
            str_contains($ua, 'claude-web') =>
            self::CLIENT_CLAUDEBOT,

            str_contains($ua, 'perplexitybot') =>
            self::CLIENT_PERPLEXITYBOT,

            str_contains($ua, 'bytespider') =>
            self::CLIENT_BYTESPIDER,

            str_contains($ua, 'google-extended') ||
            str_contains($ua, 'gemini') =>
            self::CLIENT_GEMINIBOT,

            str_contains($ua, 'meta-externalagent') ||
            str_contains($ua, 'meta-externalfetch') =>
            self::CLIENT_META_EXTERNAL_AGENT,

            str_contains($ua, 'amazonbot') =>
            self::CLIENT_AMAZONBOT,

            str_contains($ua, 'cohere-ai') =>
            self::CLIENT_COHERE,

            /*
             * Search / social bots
             */
            str_contains($ua, 'googlebot') =>
            self::CLIENT_GOOGLEBOT,

            str_contains($ua, 'bingbot') =>
            self::CLIENT_BINGBOT,

            str_contains($ua, 'duckduckbot') =>
            self::CLIENT_DUCKDUCKBOT,

            str_contains($ua, 'yandexbot') =>
            self::CLIENT_YANDEXBOT,

            str_contains($ua, 'baiduspider') =>
            self::CLIENT_BAIDUSPIDER,

            str_contains($ua, 'sogouspider') =>
            self::CLIENT_SOGOU_SPIDER,

            str_contains($ua, 'exabot') =>
            self::CLIENT_EXABOT,

            str_contains($ua, 'applebot') =>
            self::CLIENT_APPLEBOT,

            str_contains($ua, 'facebookexternalhit') ||
            str_contains($ua, 'facebookbot') =>
            self::CLIENT_FACEBOOKBOT,

            str_contains($ua, 'twitterbot') =>
            self::CLIENT_TWITTERBOT,

            str_contains($ua, 'linkedinbot') =>
            self::CLIENT_LINKEDINBOT,

            str_contains($ua, 'pinterestbot') =>
            self::CLIENT_PINTERESTBOT,

            str_contains($ua, 'semrushbot') =>
            self::CLIENT_SEMRUSHBOT,

            str_contains($ua, 'ahrefsbot') =>
            self::CLIENT_AHREFSBOT,

            str_contains($ua, 'mj12bot') =>
            self::CLIENT_MJ12BOT,

            str_contains($ua, 'dotbot') =>
            self::CLIENT_DOTBOT,

            str_contains($ua, 'petalbot') =>
            self::CLIENT_PETALBOT,

            /*
             * Microsoft
             *
             * Edge must be checked before Chrome because Edge Chromium
             * also contains Chrome/ and Safari/.
             */
            str_contains($ua, 'edgios/') ||
            str_contains($ua, 'edga/') ||
            str_contains($ua, 'edg/') ||
            str_contains($ua, 'edge/') =>
            self::CLIENT_EDGE,

            str_contains($ua, 'msie ') ||
            str_contains($ua, 'trident/') =>
            self::CLIENT_IE,

            /*
             * Opera
             *
             * Opera Chromium also contains Chrome/ and Safari/.
             */
            str_contains($ua, 'opera mini') =>
            self::CLIENT_OPERA_MINI,

            str_contains($ua, 'opera mobi') =>
            self::CLIENT_OPERA_MOBILE,

            str_contains($ua, 'opr/') ||
            str_contains($ua, 'opera/') =>
            self::CLIENT_OPERA,

            /*
             * Samsung Browser
             *
             * Samsung Browser also contains Chrome/ and Safari/.
             */
            str_contains($ua, 'samsungbrowser/') =>
            self::CLIENT_SAMSUNG,

            /*
             * Yandex
             */
            str_contains($ua, 'yabrowser/') =>
            str_contains($ua, 'mobile')
                ? self::CLIENT_YANDEX_MOBILE
                : self::CLIENT_YANDEX,

            /*
             * UC Browser
             */
            str_contains($ua, 'ucbrowser/') ||
            str_contains($ua, 'ucweb/') =>
            self::CLIENT_UC,

            /*
             * Firefox family
             */
            str_contains($ua, 'seamonkey/') =>
            self::CLIENT_SEAMONKEY,

            str_contains($ua, 'palemoon/') =>
            self::CLIENT_PALEMOON,

            str_contains($ua, 'waterfox/') =>
            self::CLIENT_WATERFOX,

            str_contains($ua, 'firefox/') ||
            str_contains($ua, 'fxios/') =>
            self::CLIENT_FIREFOX,

            /*
             * Other Chromium browsers
             *
             * Must be before Chrome.
             */
            str_contains($ua, 'vivaldi/') ||
            str_contains($ua, 'vivaldi') =>
            self::CLIENT_VIVALDI,

            str_contains($ua, 'brave/') ||
            str_contains($ua, 'brave') =>
            self::CLIENT_BRAVE,

            str_contains($ua, 'chromium/') =>
            self::CLIENT_CHROMIUM,

            /*
             * Android WebView
             *
             * WebView UA can also contain Chrome/.
             */
            (
                str_contains($ua, 'android') &&
                (
                    str_contains($ua, '; wv') ||
                    str_contains($ua, ' wv)') ||
                    str_contains($ua, 'version/4.0')
                )
            ) =>
            self::CLIENT_ANDROID_WEBVIEW,

            /*
             * Android stock browser
             */
            (
                str_contains($ua, 'android') &&
                str_contains($ua, 'version/') &&
                !str_contains($ua, 'chrome/') &&
                !str_contains($ua, 'crios/')
            ) =>
            self::CLIENT_ANDROID,

            /*
             * Other browsers
             *
             * These need to be checked before Safari/Chrome where
             * applicable.
             */
            str_contains($ua, 'midori') =>
            self::CLIENT_MIDORI,

            str_contains($ua, 'epiphany') ||
            str_contains($ua, 'gnome-web') =>
            self::CLIENT_GNOME_WEB,

            str_contains($ua, 'konqueror') =>
            self::CLIENT_KONQUEROR,

            str_contains($ua, 'maxthon') =>
            self::CLIENT_MAXTHON,

            str_contains($ua, 'slimjet') =>
            self::CLIENT_SLIMJET,

            str_contains($ua, 'puffin') =>
            self::CLIENT_PUFFIN,

            str_contains($ua, 'dolphin') =>
            self::CLIENT_DOLPHIN,

            str_contains($ua, 'qqbrowser') =>
            self::CLIENT_QQ,

            str_contains($ua, 'baidubrowser') =>
            self::CLIENT_BAIDU,

            str_contains($ua, 'sogoumobilebrowser') =>
            self::CLIENT_SOGOU,

            str_contains($ua, '360se') ||
            str_contains($ua, '360ee') =>
            self::CLIENT_360,

            /*
             * Game consoles
             */
            str_contains($ua, 'playstation') =>
            self::CLIENT_PLAYSTATION,

            str_contains($ua, 'xbox') =>
            self::CLIENT_XBOX,

            str_contains($ua, 'nintendo') =>
            self::CLIENT_NINTENDO,

            /*
             * Chrome
             *
             * IMPORTANT:
             * Chrome UA contains Safari/, so Chrome MUST be checked
             * before Safari.
             */
            str_contains($ua, 'chrome/') ||
            str_contains($ua, 'crios/') =>
            self::CLIENT_CHROME,

            /*
             * Safari
             *
             * Reached only when no Chromium browser matched.
             */
            str_contains($ua, 'safari/') =>
            self::CLIENT_SAFARI,

            default => $this->userAgent->isBot() ? self::CLIENT_BOT : self::CLIENT_UNKNOWN,
        };
        return $this->clientBrowserType = sprintf(
            '%s/%s',
            $this->isMobile() ? 'mobile' : 'desktop',
            $browser
        );
    }
}
