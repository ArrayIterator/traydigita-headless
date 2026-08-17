<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginScreenShotInterface;

/**
 * @property-read string $src
 * @property-read string $caption
 */
class PluginScreenshot implements PluginScreenShotInterface
{
    public function __construct(
        protected string $src,
        protected string $caption
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getSrc(): string
    {
        return $this->src;
    }

    /**
     * @inheritdoc
     */
    public function getCaption(): string
    {
        return $this->caption;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'src' => $this->getSrc(),
            'caption' => $this->getCaption(),
        ];
    }

    public function __serialize(): array
    {
        return [
            'src' => $this->getSrc(),
            'caption' => $this->getCaption(),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->__unserialize($data);
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __get(string $name)
    {
        return match ($name) {
            'src' => $this->getSrc(),
            'caption' => $this->getCaption(),
            default => $this->$name??null,
        };
    }

    public function __set(string $name, $value): void
    {
        // void
    }

    public function __isset(string $name): bool
    {
        return isset($this->$name);
    }

    public function __unset(string $name): void
    {
        // void
    }
}
