<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Abstracts;

use ReflectionObject;
use Throwable;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionsInterface;
use function add_action;
use function array_filter;
use function determine_locale;
use function did_action;
use function dirname;
use function doing_action;
use function load_textdomain;
use function ltrim;
use function path_is_absolute;
use function plugin_basename;
use function remove_action;
use function str_starts_with;
use function trim;

abstract class AbstractExtension implements ExtensionInterface
{
    /**
     * @var string $name The extension name.
     */
    protected string $name;

    /**
     * @var string $description The extension description.
     */
    protected string $description;

    /**
     * @var string $version The extension version.
     */
    protected string $version;

    /**
     * @var string|null $homepage The extension homepage URL,
     * if any. This property can be null if the extension does not have a homepage.
     */
    protected ?string $homepage;

    /**
     * @var string|null $supportUrl The extension support URL,
     * if any. This property can be null if the extension does not have a support URL.
     */
    protected ?string $supportUrl;

    /**
     * @var string|null $author The extension author name,
     * if any. This property can be null if the extension does not have an author.
     */
    protected ?string $author;

    /**
     * @var array<string>
     */
    protected array $keywords;

    /**
     * @var string|null $textDomain The extension text domain,
     * if any. This property can be null if the extension does not have a text domain.
     */
    protected ?string $textDomain;

    /**
     * @var string $domainPath The extension domain path.
     */
    protected string $domainPath;

    /**
     * @var bool $loaded Whether the extension has been loaded or not.
     */
    private bool $loaded;

    /**
     * @var bool $prepared Whether the extension has been prepared or not.
     */
    private bool $prepared;

    /**
     * @var bool $initialized Whether the extension has been initialized or not.
     */
    private bool $initialized = false;

    /**
     * @var bool $isCore Whether the extension is a core extension or not.
     */
    public readonly bool $isCore;

    /**
     * @var string $basename The extension basename.
     */
    public readonly string $basename;

    /**
     * @var ReflectionObject $reflection The reflection object of the extension.
     */
    public readonly ReflectionObject $reflection;

    /**
     * @param ExtensionsInterface $extensions The extensions collection.
     */
    final public function __construct(public readonly ExtensionsInterface $extensions)
    {
        $this->reflection = new ReflectionObject($this);
        $traydigita = $extensions->getContainer()->traydigita;
        $this->basename = plugin_basename($this->reflection->getFileName());
        $this->isCore = str_starts_with($this->basename, $traydigita->pluginBasename);
        if (did_action('after_setup_theme') || doing_action('after_setup_theme')) {
            $this->initialize();
        } else {
            add_action('after_setup_theme', [$this, 'initialize'], 0);
        }
    }

    /**
     * @inheritdoc
     */
    public function getExtensions(): ExtensionsInterface
    {
        return $this->extensions;
    }

    /**
     * Initialize the extension. This method is called after the 'after_setup_theme' action is fired.
     */
    final public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        if (!did_action('after_setup_theme') && !doing_action('after_setup_theme')) {
            return;
        }
        $this->initialized = true;
        remove_action('after_setup_theme', [$this, 'initialize'], 0);
        $domain = $this->getTextDomain();
        $directory = $this->getDomainPath();
        $domain = $domain ? trim($domain) : null;
        $directory = $directory ? trim($directory) : null;
        if ($directory && $domain) {
            try {
                $objectDirectory = dirname($this->reflection->getFileName());
                $directory = path_is_absolute($directory)
                    ? $directory
                    : $objectDirectory . '/' . ltrim($directory, '/\\');
                $directory = trailingslashit($directory);
                $locale = determine_locale();
                $fileName = sprintf('%s-%s.mo', $domain, $locale);
                $filePath = $directory . $fileName;
                if (file_exists($filePath)) {
                    load_textdomain($domain, $filePath);
                }
            } catch (Throwable) {
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getTextDomain(): ?string
    {
        return $this->textDomain ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getDomainPath(): ?string
    {
        return $this->domainPath ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getKeyWords(): array
    {
        if (!isset($this->keywords)) {
            return [];
        }
        return array_filter($this->keywords, 'is_string');
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        if (isset($this->name)) {
            return $this->name;
        }
        $name = get_class($this);
        $name = explode('\\', $name);
        $name = end($name);
        $this->name = $name;
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * @inheritdoc
     */
    public function getHomepage(): ?string
    {
        return $this->homepage ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getVersion(): string
    {
        return $this->version ?? self::UNKNOWN_VERSION;
    }

    /**
     * @inheritdoc
     */
    public function getSupportUrl(): ?string
    {
        return $this->supportUrl ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getAuthor(): ?string
    {
        return $this->author ?? null;
    }

    /**
     * @inheritdoc
     */
    final public function boot(ExtensionsInterface $extensions): void
    {
        if (!empty($this->loaded)
            // strict comparison to ensure the same instance of ExtensionsInterface is used
            || $extensions !== $this->extensions
        ) {
            return;
        }

        // skip if the extension is not registered in the collection
        if (!$extensions->has($this, true)) {
            return;
        }
        $this->loaded = true;
        if ($extensions->booted($this, true)) {
            return;
        }
        if (empty($this->prepared)) {
            $this->prepare($extensions);
        }
        $this->doLoad();
    }

    /**
     * @inheritdoc
     */
    final public function prepare(ExtensionsInterface $extensions): void
    {
        if (!empty($this->prepared)
            || !empty($this->loaded)
            // strict comparison to ensure the same instance of ExtensionsInterface is used
            || $extensions !== $this->extensions
        ) {
            return;
        }
        // skip if the extension is not registered in the collection
        if (!$extensions->has($this, true)
            || $extensions->booted($this, true)
        ) {
            return;
        }
        $this->prepared = true;
        $this->doPrepare();
    }

    protected function doLoad()
    {
    }

    /**
     * Prepare the extension
     *
     * @return void
     */
    protected function doPrepare()
    {
    }
}
