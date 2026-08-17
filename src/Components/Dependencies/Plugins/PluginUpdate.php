<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginUpdateInterface;
use function is_string;

/**
 * @template TSlug of string
 * @template-implements PluginUpdateInterface<TSlug>
 * @property-read TSlug $slug
 * @property-read string $plugin
 * @property-read string|null $new_version
 * @property-read string $url
 * @property-read string|null $package
 * @property-read array<string,string> $icons
 * @property-read array<string,string> $banners
 * @property-read array<string,string> $banners_rtl
 * @property-read string|null $requires_php
 * @property-read string|null $requires
 * @property-read string|null $tested_version
 * @property-read array<string> $required_plugins
 * @property-read array<string,string> $compatibility
 * @property-read string|null $upgrade_notice
 */
class PluginUpdate implements PluginUpdateInterface
{
    /**
     * @param string|null $id
     * @param TSlug $slug
     * @param string $plugin
     * @param string|null $newVersion
     * @param string $url
     * @param string|null $package
     * @param array<string,string> $icons
     * @param array<string,string> $banners
     * @param array<string,string> $bannersRtl
     * @param string|null $requiresPHP
     * @param string|null $requiresWP
     * @param string|null $testedVersion
     * @param array<string> $requiredPlugins
     * @param array<string,string> $compatibility
     * @param string|null $upgradeNotice
     */
    public function __construct(
        public ?string $id,
        protected string $slug,
        protected string $plugin,
        protected ?string $newVersion,
        protected string $url,
        protected ?string $package,
        protected array $icons,
        protected array $banners,
        protected array $bannersRtl,
        protected ?string $requiresPHP,
        protected ?string $requiresWP,
        protected ?string $testedVersion,
        protected array $requiredPlugins,
        protected array $compatibility,
        protected ?string $upgradeNotice
    ) {
        $this->slug = trim($this->slug);
        $this->plugin = trim($this->plugin);
        foreach (($this->icons ?? []) as $key => $icon) {
            if (!is_string($key) || !is_string($icon)) {
                unset($this->icons[$key]);
            }
        }
        foreach (($this->banners ?? []) as $key => $banner) {
            if (!is_string($key) || !is_string($banner)) {
                unset($this->banners[$key]);
            }
        }
        foreach (($this->bannersRtl ?? []) as $key => $bannerRtl) {
            if (!is_string($key) || !is_string($bannerRtl)) {
                unset($this->bannersRtl[$key]);
            }
        }
        foreach ($this->requiredPlugins ?? [] as $key => $requiredPlugin) {
            if (!is_string($requiredPlugin)) {
                unset($this->requiredPlugins[$key]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function isValid(): bool
    {
        return $this->slug !== '' && $this->plugin !== '';
    }

    /**
     * Create a new instance of PluginUpdate from an associative array.
     *
     * @param array $update An associative array containing plugin update information.
     * @return static A new instance of PluginUpdate.
     */
    public static function createFromUpdate(array $update): static
    {
        $id = is_string($update['id'] ?? null) ? $update['id'] : null;
        $slug = is_string($update['slug'] ?? null) ? $update['slug'] : '';
        $plugin = is_string($update['plugin'] ?? null) ? $update['plugin'] : '';
        $newVersion = is_string($update['new_version'] ?? null) ? $update['new_version'] : null;
        $url = is_string($update['url'] ?? null) ? $update['url'] : '';
        $package = is_string($update['package'] ?? null) ? $update['package'] : null;
        $icons = is_array($update['icons'] ?? null) ? $update['icons'] : [];
        $banners = is_array($update['banners'] ?? null) ? $update['banners'] : [];
        $bannersRtl = is_array($update['banners_rtl'] ?? null) ? $update['banners_rtl'] : [];
        $requiresPHP = is_string($update['requires_php'] ?? null) ? $update['requires_php'] : null;
        $requiresWP = is_string($update['requires'] ?? null) ? $update['requires'] : null;
        $testedVersion = is_string($update['tested'] ?? null) ? $update['tested'] : null;
        $requiredPlugins = is_array($update['required_plugins'] ?? null) ? $update['required_plugins'] : [];
        $compatibility = is_array($update['compatibility'] ?? null) ? $update['compatibility'] : [];
        $upgradeNotice = is_string($update['upgrade_notice'] ?? null) ? $update['upgrade_notice'] : null;
        return new static(
            $id,
            $slug,
            $plugin,
            $newVersion,
            $url,
            $package,
            $icons,
            $banners,
            $bannersRtl,
            $requiresPHP,
            $requiresWP,
            $testedVersion,
            $requiredPlugins,
            $compatibility,
            $upgradeNotice
        );
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getPlugin(): string
    {
        return $this->plugin;
    }

    public function getNewVersion(): ?string
    {
        return $this->newVersion;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getPackage(): ?string
    {
        return $this->package;
    }

    public function getIcons(): array
    {
        return $this->icons;
    }

    public function getBanners(): array
    {
        return $this->banners;
    }

    public function getBannersRtl(): array
    {
        return $this->bannersRtl;
    }

    public function getRequiresPHP(): ?string
    {
        return $this->requiresPHP;
    }

    public function getRequiresWP(): ?string
    {
        return $this->requiresWP;
    }

    public function getTestedVersion(): ?string
    {
        return $this->testedVersion;
    }

    public function getRequiredPlugins(): array
    {
        return $this->requiredPlugins;
    }

    public function getCompatibility(): array
    {
        return $this->compatibility;
    }

    public function getUpgradeNotice(): ?string
    {
        return $this->upgradeNotice;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'id' => $this->getId(),
            'slug' => $this->getSlug(),
            'plugin' => $this->getPlugin(),
            'new_version' => $this->getNewVersion(),
            'requires_php' => $this->getRequiresPHP(),
            'requires_wp', 'requires' => $this->getRequiresWP(),
            'tested_version' => $this->getTestedVersion(),
            'required_plugins' => $this->getRequiredPlugins(),
            'compatibility' => $this->getCompatibility(),
            'upgrade_notice' => $this->getUpgradeNotice(),
            default => $this->$name ?? null,
        };
    }

    /**
     * Magic method to check if a property is set.
     *
     * @param string $name The name of the property to check.
     * @return bool True if the property is set, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return $this->__get($name) !== null;
    }

    /**
     * Magic method to unset a property.
     *
     * @param string $name The name of the property to unset.
     */
    public function __unset(string $name): void
    {
        // void
    }

    /**
     * Magic method to set a property.
     *
     * @param string $name The name of the property to set.
     * @param mixed $value The value to set the property to.
     */
    public function __set(string $name, mixed $value): void
    {
        // void
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'slug' => $this->getSlug(),
            'plugin' => $this->getPlugin(),
            'new_version' => $this->getNewVersion(),
            'url' => $this->getUrl(),
            'package' => $this->getPackage(),
            'icons' => $this->getIcons(),
            'banners' => $this->getBanners(),
            'banners_rtl' => $this->getBannersRtl(),
            'requires_php' => $this->getRequiresPHP(),
            'requires' => $this->getRequiresWP(),
            'tested' => $this->getTestedVersion(),
            'required_plugins' => $this->getRequiredPlugins(),
            'compatibility' => $this->getCompatibility(),
            'upgrade_notice' => $this->getUpgradeNotice()
        ];
    }

    /**
     * @inheritdoc
     */
    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $data) : void
    {
        $this->__unserialize(unserialize($data, ['allowed_classes' => false]));
    }

    public function __serialize(): array
    {
        return $this->jsonSerialize();
    }

    public function __unserialize(array $data): void
    {
        $this->id = $data['id'] ?? null;
        $this->slug = $data['slug'] ?? '';
        $this->plugin = $data['plugin'] ?? '';
        $this->newVersion = $data['new_version'] ?? null;
        $this->url = $data['url'] ?? '';
        $this->package = $data['package'] ?? null;
        $this->icons = $data['icons'] ?? [];
        $this->banners = $data['banners'] ?? [];
        $this->bannersRtl = $data['banners_rtl'] ?? [];
        $this->requiresPHP = $data['requires_php'] ?? null;
        $this->requiresWP = $data['requires'] ?? null;
        $this->testedVersion = $data['tested'] ?? null;
        $this->requiredPlugins = $data['required_plugins'] ?? [];
        $this->compatibility = $data['compatibility'] ?? [];
        $this->upgradeNotice = $data['upgrade_notice'] ?? null;
    }
}
