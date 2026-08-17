<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Abstracts;

use ReflectionObject;
use Throwable;
use TrayDigita\WP\Headless\Resource\Components\Extensions;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\ExtensionsInterface;
use function add_action;
use function array_filter;
use function debug_backtrace;
use function determine_locale;
use function did_action;
use function dirname;
use function doing_action;
use function end;
use function explode;
use function file_exists;
use function get_class;
use function load_textdomain;
use function path_is_absolute;
use function plugin_basename;
use function remove_action;
use function sprintf;
use function trailingslashit;
use function trim;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

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
     * @var bool $booted Whether the extension has been loaded or not.
     */
    private bool $booted;

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
    private bool $isCore;

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
        $file = $this->reflection->getFileName();
        $this->basename = plugin_basename($file);
        if (did_action('after_setup_theme') || doing_action('after_setup_theme')) {
            $this->initialize();
        } else {
            add_action('after_setup_theme', [$this, 'initialize'], 0);
        }
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->getName(),
            'class_name' => get_class($this),
            'description' => $this->getDescription(),
            'version' => $this->getVersion(),
            'homepage' => $this->getHomepage(),
            'author' => $this->getAuthor(),
            'keywords' => $this->getKeyWords(),
            'support_url' => $this->getSupportUrl(),
            'active' => $this->booted??false,
            'text_domain' => $this->getTextDomain(),
            'is_core' => $this->isCore(),
            'logo' => $this->getLogo(),
        ];
    }

    /**
     * Should the extension be active. This method checks if the extension
     * is a core extension and if it should be active.
     *
     * @return bool
     */
    final public function shouldBeActive(): bool
    {
        return !$this->isCore() || $this->coreShouldBeActive();
    }

    /**
     * Get the priority of the core extension. This method is only called if the extension
     * is a core extension and should be active.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }

    /**
     * Check if core extension should be active. This method is only called if the extension
     * is a core extension and should be active.
     *
     * @return bool
     */
    protected function coreShouldBeActive(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    final public function isCore(): bool
    {
        return $this->isCore ??= $this->extensions->isCore($this);
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
                $directory = path_is_absolute($directory)
                    ? trailingslashit($directory)
                    : dirname($this->reflection->getFileName()) . '/' . trim($directory, '/\\') . '/';
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
        if (!empty($this->booted)
            // strict comparison to ensure the same instance of ExtensionsInterface is used
            || $extensions !== $this->extensions
        ) {
            return;
        }

        // skip if the extension is not registered in the collection
        if (!$extensions->has($this, true)) {
            return;
        }
        $debugBoot = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $className = $debugBoot['class'] ?? null;
        if ($className !== Extensions::class) {
            return; // skip if the boot method is not called from the Extensions class
        }
        $this->booted = true;
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
            || !empty($this->booted)
            // strict comparison to ensure the same instance of ExtensionsInterface is used
            || $extensions !== $this->extensions
        ) {
            return;
        }
        // skip if the extension is not registered in the collection
        if (!$extensions->has($this, true) || $extensions->booted($this, true)) {
            return;
        }
        $debugBoot = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? [];
        $className = $debugBoot['class'] ?? null;
        if (!$className || $className !== Extensions::class && $className !== __CLASS__) {
            return; // skip if the boot method is not called from the Extensions class
        }
        $this->prepared = true;
        $this->doPrepare();
    }

    /**
     * @inheritdoc
     */
    public function getLogo() : ?string
    {
        return null;
    }

    /**
     * Load the extension
     *
     * @return void
     */
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
