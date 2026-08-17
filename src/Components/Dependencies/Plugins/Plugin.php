<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Enums\PluginType;
use TrayDigita\WP\Headless\Resource\Interfaces\PluginInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginInfoInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginUpdateInterface;

/**
 * @template TSlug of string
 * @template-implements PluginInterface<TSlug>
 * @property-read TSlug $slug
 * @property-read PluginType $type
 * @property-read bool $required
 * @property PluginInfoInterface<TSlug> $info
 * @property PluginUpdateInterface<TSlug>|null $update
 */
class Plugin implements PluginInterface
{
    /**
     * @var PluginUpdateInterface<TSlug>|null
     */
    protected ?PluginUpdateInterface $update;

    /**
     * @param TSlug $slug
     * @param PluginType $type
     * @param bool $required
     * @param PluginInfoInterface<TSlug> $info
     * @param PluginUpdateInterface<TSlug>|null $update
     */
    public function __construct(
        string $slug,
        protected PluginType $type,
        protected bool $required,
        protected PluginInfoInterface $info,
        ?PluginUpdateInterface $update = null
    ) {
        $this->slug = trim($slug);
        $this->setUpdate($update);
    }

    /**
     * @inheritdoc
     */
    public function isValid(): bool
    {
        return !empty($this->slug) && $this->info->getSlug() === $this->getSlug();
    }

    /**
     * @inheritdoc
     */
    public function getType(): PluginType
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * @inheritdoc
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @inheritdoc
     */
    public function getPluginInfo(): PluginInfoInterface
    {
        return $this->info;
    }

    /**
     * @inheritdoc
     */
    public function setUpdate(?PluginUpdateInterface $update): void
    {
        // force null if the update slug is not match with the plugin slug
        $update = $update?->getSlug() !== $this->getSlug() ? null : $update;
        $this->update = $update;
    }

    /**
     * @inheritdoc
     */
    public function setPluginInfo(PluginInfoInterface $info): bool
    {
        if ($info->getSlug() !== $this->getSlug()) {
            return false;
        }
        $this->info = $info;
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getUpdate(): ?PluginUpdateInterface
    {
        return $this->update;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'slug' => $this->getSlug(),
            'type' => $this->getType(),
            'required' => $this->isRequired(),
            'info' => $this->getPluginInfo(),
            'update' => $this->getUpdate(),
            default => $this->$name ?? null,
        };
    }

    public function __set(string $name, $value): void
    {
        if ($name === 'update' && ($value === null || $value instanceof PluginUpdateInterface)) {
            $this->setUpdate($value);
        }
        if ($name === 'info' && $value instanceof PluginInfoInterface) {
            $this->setPluginInfo($value);
        }
    }
}
