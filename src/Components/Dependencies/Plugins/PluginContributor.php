<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginContributorInterface;
use function strtolower;
use function trim;

class PluginContributor implements PluginContributorInterface
{
    /**
     * @var string $username The username of the contributor,
     * typically in lowercase and trimmed of whitespace.
     */
    public readonly string $username;

    /**
     * @var string $profileUrl The URL to the contributor's profile page.
     */
    public readonly string $profileUrl;

    /**
     * @var string $avatarUrl The URL to the contributor's avatar image.
     */
    public readonly string $avatarUrl;

    /**
     * @var string $displayName The display name of the contributor,
     * typically trimmed of whitespace.
     */
    public readonly string $displayName;

    /**
     * Constructor for the PluginContributor class.
     *
     * @param string $username The username of the contributor.
     * @param string $profileUrl The URL to the contributor's profile page.
     * @param string $avatarUrl The URL to the contributor's avatar image.
     * @param string $displayName The display name of the contributor.
     */
    public function __construct(
        string $username,
        string $profileUrl,
        string $avatarUrl,
        string $displayName
    ) {
        $this->username = strtolower(trim($username));
        $this->profileUrl = trim($profileUrl);
        $this->avatarUrl = trim($avatarUrl);
        $this->displayName = trim($displayName);
    }

    /**
     * @inheritdoc
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @inheritdoc
     */
    public function getProfileUrl(): string
    {
        return $this->profileUrl;
    }

    /**
     * @inheritdoc
     */
    public function getAvatarUrl(): string
    {
        return $this->avatarUrl;
    }

    /**
     * @inheritdoc
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'profile' => $this->getProfileUrl(),
            'avatar' => $this->getAvatarUrl(),
            'display_name' => $this->getDisplayName()
        ];
    }

    /**
     * Magic method to get properties dynamically.
     *
     * @param string $name The name of the property to retrieve.
     * @return mixed The value of the requested property, or null if it doesn't exist.
     */
    public function __get(string $name)
    {
        return match ($name) {
            'username' => $this->getUsername(),
            'profile' => $this->getProfileUrl(),
            'avatar' => $this->getAvatarUrl(),
            'display_name' => $this->getDisplayName(),
            default => $this->$name??null,
        };
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
        return [
            'username' => $this->getUsername(),
            'profile' => $this->getProfileUrl(),
            'avatar' => $this->getAvatarUrl(),
            'display_name' => $this->getDisplayName()
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->username = $data['username'] ?? '';
        $this->profileUrl = $data['profile'] ?? '';
        $this->avatarUrl = $data['avatar'] ?? '';
        $this->displayName = $data['display_name'] ?? '';
    }
}
