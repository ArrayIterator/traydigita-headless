<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginContributorInterface;
use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginContributorsInterface;
use function strtolower;
use function trim;

/**
 * @template TUsername of string
 * @template TContributor of PluginContributorInterface
 * /
 * @template-implements PluginContributorsInterface<TUsername, TContributor>
 */
class PluginContributors implements PluginContributorsInterface
{
    /**
     * @var array<TUsername, TContributor>
     */
    private array $contributors;

    /**
     * @param TContributor ...$contributors
     */
    public function __construct(PluginContributorInterface ...$contributors)
    {
        foreach ($contributors as $contributor) {
            $username = strtolower(trim($contributor->getUsername()));
            $this->contributors[$username] = $contributor;
        }
    }

    /**
     * @inheritdoc
     */
    public function has(string $username): bool
    {
        $username = strtolower(trim($username));
        return isset($this->contributors[$username]);
    }

    /**
     * @inheritdoc
     */
    public function get(string $username): ?PluginContributorInterface
    {
        $username = strtolower(trim($username));
        return $this->contributors[$username] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function add(PluginContributorInterface $contributor): void
    {
        $username = strtolower(trim($contributor->getUsername()));
        $this->contributors[$username] = $contributor;
    }

    /**
     * @inheritdoc
     */
    public function remove(string|PluginContributorInterface $contributor): ?PluginContributorInterface
    {
        if ($contributor instanceof PluginContributorInterface) {
            $username = strtolower(trim($contributor->getUsername()));
        } else {
            $username = strtolower(trim($contributor));
        }
        $contributor = $this->contributors[$username] ?? null;
        unset($this->contributors[$username]);
        return $contributor;
    }

    /**
     * @inheritdoc
     */
    public function all(): array
    {
        return $this->contributors;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }

    /**
     * Magic method to get a contributor by username.
     *
     * @param string $name The username of the contributor.
     * @return TContributor|null The contributor object, or null if not found.
     */
    public function __get(string $name): ?PluginContributorInterface
    {
        return $this->get($name);
    }

    /**
     * Magic method to set a contributor by username.
     *
     * @param string $name The username of the contributor.
     * @param TContributor $contributor The contributor object to set.
     */
    public function __set(string $name, mixed $contributor)
    {
        if (!$contributor instanceof PluginContributorInterface) {
            return;
        }
        $this->add($contributor);
    }

    /**
     * Magic method to check if a contributor exists by username.
     *
     * @param string $name The username of the contributor.
     * @return bool True if the contributor exists, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
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
        return $this->all();
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $contributor) {
            if ($contributor instanceof PluginContributorInterface) {
                $this->add($contributor);
            }
        }
    }
}
