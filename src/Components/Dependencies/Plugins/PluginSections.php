<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginSectionsInterface;

class PluginSections implements PluginSectionsInterface
{
    /**
     * @param string|null $descriptions
     * @param string|null $installation
     * @param string|null $changeLog
     * @param string|null $faq
     * @param string|null $reviews
     */
    public function __construct(
        protected ?string $descriptions = null,
        protected ?string $installation = null,
        protected ?string $changeLog = null,
        protected ?string $faq = null,
        protected ?string $reviews = null
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getDescriptions(): ?string
    {
        return $this->descriptions;
    }

    /**
     * @inheritdoc
     */
    public function getInstallation(): ?string
    {
        return $this->installation;
    }

    /**
     * @inheritdoc
     */
    public function getChangeLog(): ?string
    {
        return $this->changeLog;
    }

    /**
     * @inheritdoc
     */
    public function getFAQ(): ?string
    {
        return $this->faq;
    }

    /**
     * @inheritdoc
     */
    public function getReviews(): ?string
    {
        return $this->reviews;
    }

    public function __serialize(): array
    {
        return [
            'descriptions' => $this->descriptions,
            'installation' => $this->installation,
            'changeLog' => $this->changeLog,
            'faq' => $this->faq,
            'reviews' => $this->reviews
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->descriptions = $data['descriptions'] ?? null;
        $this->installation = $data['installation'] ?? null;
        $this->changeLog = $data['changeLog'] ?? null;
        $this->faq = $data['faq'] ?? null;
        $this->reviews = $data['reviews'] ?? null;
    }

    public function jsonSerialize(): array
    {
        return [
            'descriptions' => $this->descriptions,
            'installation' => $this->installation,
            'changeLog' => $this->changeLog,
            'faq' => $this->faq,
            'reviews' => $this->reviews
        ];
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }
}
