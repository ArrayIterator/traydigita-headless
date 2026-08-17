<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use DI;
use Google\Site_Kit_Dependencies\Google\Service\Adsense\Site;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use TrayDigita\WP\Headless\Resource\Database\WordPressDatabase;
use TrayDigita\WP\Headless\Resource\Database\WPDB;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionsInterface;
use TrayDigita\WP\Headless\Resource\Networks\Cloudflare;
use TrayDigita\WP\Headless\Resource\Networks\Ip;
use TrayDigita\WP\Headless\Resource\Networks\UserAgent;
use TrayDigita\WP\Headless\Resource\Networks\UserAgentGenerator;
use TrayDigita\WP\Headless\Resource\TrayDigita;
use function DI\autowire;
use function DI\factory;
use function DI\get;
use function DI\value;
use function plugin_dir_path;
use function wp_normalize_path;

/**
 * @mixin DI\Container
 * @property-read Container $container
 * @property-read Container $ContainerInterface
 * @property-read ServerRequestInterface $server_request
 * @property-read ServerRequestInterface $serverRequest
 * @property-read ServerRequestInterface $request
 * @property-read ExtensionsInterface $extensions
 * @property-read Feature $feature
 * @property-read TrayDigita $traydigita
 * @property-read Assets $assets
 * @property-read Attributes $attributes
 * @property-read AdminMenu $admin_menu
 * @property-read AdminMenu $adminMenu
 * @property-read KeyStorage $key_storage
 * @property-read KeyStorage $keyStorage
 * @property-read Options $options
 * @property-read Photon $photon
 * @property-read PopularPosts $popular_posts
 * @property-read PopularPosts $popularPosts
 * @property-read PostUtil $post_util
 * @property-read PostUtil $postUtil
 * @property-read PluginInstall $plugin_install
 * @property-read PluginInstall $pluginInstall
 * @property-read Rest $rest
 * @property-read Site $site
 * @property-read SiteOptions $site_options
 * @property-read SiteOptions $siteOptions
 * @property-read SiteTransients $site_transients
 * @property-read SiteTransients $siteTransients
 * @property-read StatelessTokenizer $stateless_tokenizer
 * @property-read StatelessTokenizer $statelessTokenizer
 * @property-read StatelessTokenizer $tokenizer
 * @property-read StatelessAuthenticator $stateless_authenticator
 * @property-read StatelessAuthenticator $statelessAuthenticator
 * @property-read User $user
 * @property-read Uuid $uuid
 * @property-read Database $database
 * @property-read WPDB $wpdb
 * @property-read WordPressDatabase $wordpress_database
 * @property-read WordPressDatabase $wordpressDatabase
 * @property-read Cloudflare $cloudflare
 * @property-read Cloudflare $cloudFlare
 * @property-read Ip $ip
 * @property-read UserAgent $user_agent
 * @property-read UserAgent $userAgent
 * @property-read UserAgentGenerator $user_agent_generator
 * @property-read UserAgentGenerator $userAgentGenerator
 * @property-read bool $development
 * @property-read bool $is_development
 * @property-read string $plugin_file
 * @property-read string $plugin_dir
 * @property-read string $plugin_url
 */
class Container implements ContainerInterface
{
    /**
     * The DI container instance.
     *
     * @var DI\Container
     */
    private DI\Container $container;

    /**
     * Errors that occurred during the container's operation.
     *
     * @var array<string, Throwable>
     */
    private array $errors;

    /**
     * The plugin directory path.
     *
     * @var string
     */
    public readonly string $pluginDir;

    /**
     * The plugin URL.
     *
     * @var string
     */
    public readonly string $pluginUrl;

    /**
     * Container constructor.
     * @param bool $development Indicates whether the plugin is in development mode.
     * @param ?array{
     *     host: string,
     *     port: int,
     *     url: string,
     * } $developmentServersJson An array of development server URLs.
     * @param string $pluginFile The path to the main plugin file.
     */
    public function __construct(
        public readonly bool $development,
        public readonly ?array $developmentServersJson,
        public readonly string $pluginFile
    ) {
        $this->pluginDir = wp_normalize_path(plugin_dir_path($this->pluginFile));
        $this->pluginUrl = plugin_dir_url($this->pluginFile);
    }

    /**
     * Define the factory definitions for the DI container.
     *
     * @return array<string, mixed> The array of factory definitions.
     */
    private function factoryDefinitions(): array
    {
        return [
            // argument string
            'development' => value($this->development),
            'is_development' => get('development'),
            'pluginFile' => value($this->pluginFile),
            'plugin_file' => get('pluginFile'),
            'pluginDir' => value($this->pluginDir),
            'plugin_dir' => get('pluginDir'),
            'pluginUrl' => value($this->pluginUrl),
            'plugin_url' => get('pluginUrl'),
            // self
            ContainerInterface::class => value($this),
            self::class => value($this),
            DI\Container::class => static fn($container) => $container->get(ContainerInterface::class),
            'container' => static fn($container) => $container->get(ContainerInterface::class),
            // third party
            ServerRequestInterface::class => factory([ServerRequest::class, 'fromGlobals']),
            ServerRequest::class => get(ServerRequestInterface::class),
            'server_request' => get(ServerRequestInterface::class),
            'serverRequest' => get(ServerRequestInterface::class),
            'request' => get(ServerRequestInterface::class),
            // libs
            Assets::class => autowire(Assets::class),
            'assets' => get(Assets::class),
            Feature::class => autowire(Feature::class),
            'feature' => get(Feature::class),
            TrayDigita::class => autowire(TrayDigita::class),
            'traydigita' => get(TrayDigita::class),
            Attributes::class => autowire(Attributes::class),
            'attributes' => get(Attributes::class),
            AdminMenu::class => autowire(AdminMenu::class),
            'admin_menu' => get(AdminMenu::class),
            'adminMenu' => get(AdminMenu::class),
            ExtensionsInterface::class => autowire(Extensions::class),
            Extensions::class => autowire(Extensions::class),
            'extensions' => get(ExtensionsInterface::class),
            KeyStorage::class => autowire(KeyStorage::class),
            'key_storage' => get(KeyStorage::class),
            'keyStorage' => get(KeyStorage::class),
            Options::class => autowire(Options::class),
            'options' => get(Options::class),
            Photon::class => autowire(Photon::class),
            'photon' => get(Photon::class),
            PopularPosts::class => autowire(PopularPosts::class),
            'popular_posts' => get(PopularPosts::class),
            'popularPosts' => get(PopularPosts::class),
            PostUtil::class => autowire(PostUtil::class),
            'post_util' => get(PostUtil::class),
            'postUtil' => get(PostUtil::class),
            PluginInstall::class => autowire(PluginInstall::class),
            'plugin_install' => get(PluginInstall::class),
            'pluginInstall' => get(PluginInstall::class),
            Rest::class => autowire(Rest::class),
            'rest' => get(Rest::class),
            Site::class => autowire(Site::class),
            'site' => get(Site::class),
            SiteOptions::class => autowire(SiteOptions::class),
            'site_options' => get(SiteOptions::class),
            'siteOptions' => get(SiteOptions::class),
            SiteTransients::class => autowire(SiteTransients::class),
            'site_transients' => get(SiteTransients::class),
            'siteTransients' => get(SiteTransients::class),
            StatelessAuthenticator::class => autowire(StatelessAuthenticator::class),
            'stateless_authenticator' => get(StatelessAuthenticator::class),
            'statelessAuthenticator' => get(StatelessAuthenticator::class),
            StatelessTokenizer::class => autowire(StatelessTokenizer::class),
            'stateless_tokenizer' => get(StatelessTokenizer::class),
            'statelessTokenizer' => get(StatelessTokenizer::class),
            'tokenizer' => get(StatelessTokenizer::class),
            User::class => autowire(User::class),
            'user' => get(User::class),
            Uuid::class => autowire(Uuid::class),
            'uuid' => get(Uuid::class),
            // Database
            Database::class => autowire(Database::class),
            'database' => get(Database::class),
            WPDB::class => autowire(WPDB::class),
            'wpdb' => static fn($container) => $container->get(WPDB::class),
            WordPressDatabase::class => autowire(WordPressDatabase::class),
            'wordpress_database' => get(WordPressDatabase::class),
            'wordpressDatabase' => get(WordPressDatabase::class),
            // networks
            Cloudflare::class => autowire(Cloudflare::class),
            'cloudflare' => get(Cloudflare::class),
            'cloudFlare' => get(Cloudflare::class),
            Ip::class => static fn (DI\Container $container) => $container->get(Cloudflare::class)->ip,
            'ip' => get(Ip::class),
            UserAgent::class => autowire(UserAgent::class),
            'user_agent' => get(UserAgent::class),
            'userAgent' => get(UserAgent::class),
            UserAgentGenerator::class => autowire(UserAgentGenerator::class),
            'user_agent_generator' => get(UserAgentGenerator::class),
            'userAgentGenerator' => get(UserAgentGenerator::class),
        ];
    }

    /**
     * Get the DI container instance.
     *
     * @return DI\Container
     */
    public function getContainer(): DI\Container
    {
        return $this->container ??= DI\Container::create($this->factoryDefinitions());
    }

    /**
     * Magic method to handle dynamic method calls.
     *
     * @param string $name The name of the method being called.
     * @param array $arguments The arguments passed to the method.
     * @return mixed The result of the method call on the DI container.
     */
    public function __call(string $name, array $arguments)
    {
        return $this->getContainer()->$name(...$arguments);
    }

    /**
     * Magic method to handle dynamic property access.
     *
     * @param string $name The name of the property being accessed.
     * @return mixed The value of the property on the DI container.
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Magic method to handle dynamic property setting.
     *
     * @param string $name The name of the property being set.
     * @param mixed $value The value to set for the property.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Magic method to check if a property is set.
     *
     * @param string $name The name of the property being checked.
     * @return bool True if the property is set, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Magic method to unset a property.
     *
     * @param string $name The name of the property being unset.
     */
    public function __unset(string $name): void
    {
    }

    /**
     * Get an entry from the container by its identifier.
     *
     * @template T of object
     * @param string|class-string<T> $id
     * @return T|mixed|string
     */
    public function get(string $id): mixed
    {
        try {
            return $this->getContainer()->get($id);
        } catch (Throwable $e) {
            $this->errors[$id] = $e;
            return null;
        }
    }

    /**
     * Check if the container has an entry for the specified identifier.
     *
     * @template T of object
     * @param string|class-string<T> $id
     * @return bool true if the container has the specified entry, false otherwise.
     */
    public function has(string $id): bool
    {
        return $this->getContainer()->has($id);
    }

    /**
     * Get the errors that occurred during the container's operation.
     *
     * @return array<string, Throwable> An associative array of errors,
     *      where the keys are the identifiers and the values are the corresponding exceptions.
     */
    public function getErrors() : array
    {
        return $this->errors ?? [];
    }
}
