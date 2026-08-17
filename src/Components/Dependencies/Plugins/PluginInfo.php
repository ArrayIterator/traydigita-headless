<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use DateTimeInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginContributorsInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginInfoInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginRatingInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginSectionsInterface;

/**
 * @template TSlug of string
 * @template-implements PluginInfoInterface<TSlug>
 */
class PluginInfo implements PluginInfoInterface
{
    protected string $name;

    /**
     * @var TSlug $slug
     */
    protected string $slug;

    protected string $version;

    protected ?string $author = null;

    protected ?string $authorProfile = null;

    protected ?string $requiresPHP = null;

    protected ?string $requiresWP = null;

    protected ?string $testedVersion = null;

    /** @var ?array<string> */
    protected ?array $requiresPlugins = null;

    protected ?PluginRatingInterface $ratings = null;

    protected ?string $supportUrl = null;

    protected ?int $supportThreads = null;

    protected ?int $supportThreadsResolved = null;

    protected ?PluginContributorsInterface $contributors = null;

    protected ?string $homepage = null;

    protected ?string $downloadLink = null;

    protected ?int $activeInstalls = null;

    protected ?DateTimeInterface $lastUpdated = null;

    protected ?DateTimeInterface $added = null;

    protected ?array $upgradeNotice = null;

    /** @var ?array<PluginScreenShotInterface> */
    protected ?array $screenshots = null;

    /** @var ?array<string, string> */
    protected ?array $tags = null;

    protected ?PluginSectionsInterface $sections = null;

    /** @var ?array<string, string> */
    protected ?array $versions = null;

    protected string|false|null $businessModel = null;

    protected ?string $repositoryUrl = null;

    protected ?string $commercialSupportUrl = null;

    protected ?string $donateLink = null;

    /** @var ?array<string, string> */
    protected ?array $banners = null;

    protected ?string $previewLink = null;

    public function __construct(
        string $slug,
        string $name,
        string $version
    ) {
        $this->slug = trim($slug);
        $this->name = trim($name);
        $this->version = trim($version);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
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
    public function getVersion(): string
    {
        return $this->version;
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
    public function getAuthorProfile(): ?string
    {
        return $this->authorProfile ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getRequiresPHP(): ?string
    {
        return $this->requiresPHP ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getRequiresWP(): ?string
    {
        return $this->requiresWP ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getTestedVersion(): ?string
    {
        return $this->testedVersion ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getRequiresPlugins(): ?array
    {
        return $this->requiresPlugins ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getRatings(): ?PluginRatingInterface
    {
        return $this->ratings ?? null;
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
    public function getSupportThreads(): ?int
    {
        return $this->supportThreads ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getSupportThreadsResolved(): ?int
    {
        return $this->supportThreadsResolved ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getContributors(): ?PluginContributorsInterface
    {
        return $this->contributors ?? null;
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
    public function getDownloadLink(): ?string
    {
        return $this->downloadLink ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getActiveInstalls(): ?int
    {
        return $this->activeInstalls ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getLastUpdated(): ?DateTimeInterface
    {
        return $this->lastUpdated ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getAdded(): ?DateTimeInterface
    {
        return $this->added ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getUpgradeNotice(): ?array
    {
        return $this->upgradeNotice ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getScreenshots(): ?array
    {
        return $this->screenshots ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getTags(): ?array
    {
        return $this->tags ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getVersions(): ?array
    {
        return $this->versions ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getBusinessModel(): string|null|false
    {
        return $this->businessModel ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryUrl(): ?string
    {
        return $this->repositoryUrl ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getCommercialSupportUrl(): ?string
    {
        return $this->commercialSupportUrl ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getDonateLink(): ?string
    {
        return $this->donateLink ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getBanners(): ?array
    {
        return $this->banners ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getPreviewLink(): ?string
    {
        return $this->previewLink ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getSections(): ?PluginSectionsInterface
    {
        return $this->sections ?? null;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function setAuthorProfile(?string $authorProfile): void
    {
        $this->authorProfile = $authorProfile;
    }

    public function setRequiresPHP(?string $requiresPHP): void
    {
        $this->requiresPHP = $requiresPHP;
    }

    public function setRequiresWP(?string $requiresWP): void
    {
        $this->requiresWP = $requiresWP;
    }

    public function setTestedVersion(?string $testedVersion): void
    {
        $this->testedVersion = $testedVersion;
    }

    public function setRequiresPlugins(?array $requiresPlugins): void
    {
        $this->requiresPlugins = $requiresPlugins;
    }

    public function setRatings(?PluginRatingInterface $ratings): void
    {
        $this->ratings = $ratings;
    }

    public function setSupportUrl(?string $supportUrl): void
    {
        $this->supportUrl = $supportUrl;
    }

    public function setSupportThreads(?int $supportThreads): void
    {
        $this->supportThreads = $supportThreads;
    }

    public function setSupportThreadsResolved(?int $supportThreadsResolved): void
    {
        $this->supportThreadsResolved = $supportThreadsResolved;
    }

    public function setContributors(?PluginContributorsInterface $contributors): void
    {
        $this->contributors = $contributors;
    }

    public function setHomepage(?string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function setDownloadLink(?string $downloadLink): void
    {
        $this->downloadLink = $downloadLink;
    }

    public function setActiveInstalls(?int $activeInstalls): void
    {
        $this->activeInstalls = $activeInstalls;
    }

    public function setLastUpdated(?DateTimeInterface $lastUpdated): void
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function setAdded(?DateTimeInterface $added): void
    {
        $this->added = $added;
    }

    public function setUpgradeNotice(?array $upgradeNotice): void
    {
        $this->upgradeNotice = $upgradeNotice;
    }

    public function setScreenshots(?array $screenshots): void
    {
        $this->screenshots = $screenshots;
    }

    public function setTags(?array $tags): void
    {
        $this->tags = $tags;
    }

    public function setSections(?PluginSectionsInterface $sections): void
    {
        $this->sections = $sections;
    }

    public function setVersions(?array $versions): void
    {
        $this->versions = $versions;
    }

    public function setBusinessModel(bool|string|null $businessModel): void
    {
        $this->businessModel = $businessModel;
    }

    public function setRepositoryUrl(?string $repositoryUrl): void
    {
        $this->repositoryUrl = $repositoryUrl;
    }

    public function setCommercialSupportUrl(?string $commercialSupportUrl): void
    {
        $this->commercialSupportUrl = $commercialSupportUrl;
    }

    public function setDonateLink(?string $donateLink): void
    {
        $this->donateLink = $donateLink;
    }

    public function setBanners(?array $banners): void
    {
        $this->banners = $banners;
    }

    public function setPreviewLink(?string $previewLink): void
    {
        $this->previewLink = $previewLink;
    }

    /**
     * @return array{
     *     "name": string,
     *     "slug": string,
     *     "version": string,
     *     "author": string|null,
     *     "author_profile": string|null,
     *     "contributors": ?PluginContributorsInterface,
     *     "requires": string|null,
     *     "tested": string|null,
     *     "requires_php": string|null,
     *     "requires_plugins": ?array<string>,
     *     "rating": int|null,
     *     "ratings": ?PluginRatingInterface,
     *     "num_ratings": int|null,
     *     "support_url": string|null,
     *     "support_threads": int|null,
     *     "support_threads_resolved": int|null,
     *     "active_installs": int|null,
     *     "last_updated": string|null,
     *     "added": string|null,
     *     "screenshots": ?array<PluginScreenShotInterface>,
     *     "tags": ?array<string, string>,
     *     "sections": ?PluginSectionsInterface,
     *     "download_link": string|null,
     *     "upgrade_notice": ?array,
     *     "versions": ?array<string, string>,
     *     "business_model": string|null|false,
     *     "repository_url": string|null,
     *     "commercial_support_url": string|null,
     *     "donate_link": string|null,
     *     "banners": ?array<string, string>,
     *     "preview_link": string|null
     * }
     */
    public function toArray(): array
    {
        $rating = 0;
        $ratingCount = 0;
        $ratings = $this->getRatings();
        if ($ratings instanceof PluginRatingInterface) {
            $rating = $ratings->getAverageRating();
            $ratingCount = $ratings->getRatingCount();
        }
        return [
            "name" => $this->getName(),
            "slug" => $this->getSlug(),
            "version" => $this->getVersion(),
            "author" => $this->getAuthor(),
            "author_profile" => $this->getAuthorProfile(),
            "contributors" => $this->getContributors(),
            "requires" => $this->getRequiresWP(),
            "tested" => $this->getTestedVersion(),
            "requires_php" => $this->getRequiresPhp(),
            "requires_plugins" => $this->getRequiresPlugins(),
            "rating" => $rating,
            "ratings" => $this->getRatings(),
            "num_ratings" => $ratingCount,
            "support_url" => $this->getSupportUrl(),
            "support_threads" => $this->getSupportThreads(),
            "support_threads_resolved" => $this->getSupportThreadsResolved(),
            "active_installs" => $this->getActiveInstalls(),
            "last_updated" => $this->getLastUpdated(),
            "added" => $this->getAdded(),
            "screenshots" => $this->getScreenshots(),
            "tags" => $this->getTags(),
            "sections" => $this->getSections(),
            "download_link" => $this->getDownloadLink(),
            "upgrade_notice" => $this->getUpgradeNotice(),
            "versions" => $this->getVersions(),
            "business_model" => $this->getBusinessModel(),
            "repository_url" => $this->getRepositoryUrl(),
            "commercial_support_url" => $this->getCommercialSupportUrl(),
            "donate_link" => $this->getDonateLink(),
            "banners" => $this->getBanners(),
            "preview_link" => $this->getPreviewLink()
        ];
    }

    public function __get(string $name)
    {
        return $this->toArray()[$name] ?? null;
    }

    public function jsonSerialize(): array
    {
        $array = $this->toArray();
        $array['added'] = $this->getAdded()?->format('Y-m-d') ?? null;
        $array['last_updated'] = $this->getLastUpdated()?->format(DateTimeInterface::ATOM) ?? null;
        return $array;
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}
