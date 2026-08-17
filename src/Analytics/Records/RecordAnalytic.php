<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Analytics\Records;

use ArrayAccess;
use Countable;
use JsonSerializable;
use Serializable;
use Stringable;
use TrayDigita\WP\Headless\Resource\Features\Image;
use WP_Post;
use function get_post;
use function in_array;
use function is_string;
use function serialize;
use function trim;
use function unserialize;

/**
 * @property-read int $id
 * @property-read string $slug
 * @property-read int $count
 * @property-read ?WP_Post $post
 */
final class RecordAnalytic implements Serializable, JsonSerializable, Countable, Stringable, ArrayAccess
{
    private string $slug;

    private int $id;

    private int $count;

    private WP_Post|false $post;

    private bool $mismatched_slug = false;

    private int|false $thumbnailId;

    private Image $featureImage;

    public function __construct(
        string $slug,
        int $id,
        int $count
    ) {
        $this->slug = $slug;
        $this->id = $id;
        $this->count = $count;
    }

    public static function create(string $slug, int $id, int $count): self
    {
        return new self($slug, $id, $count);
    }

    public static function fromPost(WP_Post $post, int $count): self
    {
        $record = new self($post->post_name, $post->ID, $count);
        $record->post = $post;
        return $record;
    }

    public function withCount(int $count): self
    {
        $new = clone $this;
        $new->{'count'} = $count;
        return $new;
    }

    public function getFeatureImage(): Image
    {
        return $this->featureImage ??= Image::create($this->getPost());
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPost(): ?WP_Post
    {
        if (isset($this->post)) {
            return $this->post ?: null;
        }
        if ($this->id <= 0) {
            return null;
        }
        $this->mismatched_slug = true;
        $post = get_post($this->id);
        $this->post = false;
        if ($post instanceof WP_Post) {
            $this->post = $post;
            if ($this->slug === $this->post->post_name) {
                $this->mismatched_slug = false;
            } elseif (trim($this->slug, '/') === $this->post->post_name) {
                $this->mismatched_slug = false;
            } else {
                $this->mismatched_slug = true;
            }
        }
        return $this->post;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function valid(): bool
    {
        if ($this->count < 0 || $this->id <= 0) {
            return false;
        }
        $post = $this->getPost();
        return $post && $post->ID === $this->id && !$this->mismatched_slug;
    }

    /**
     * @return array{
     *     id: int,
     *     slug: string,
     *     count: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'count' => $this->count
        ];
    }

    public function serialize(): ?string
    {
        return serialize($this->toArray());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array{
     *     id: int,
     *     slug: string,
     *     count: int
     * } $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->slug = $data['slug'];
        $this->count = $data['count'];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function count(): int
    {
        return $this->count;
    }

    public function __toString(): string
    {
        return $this->slug;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'slug' => $this->getSlug(),
            'id' => $this->getId(),
            'count' => $this->getCount(),
            'post' => $this->getPost(),
            default => null,
        };
    }

    public function __set(string $name, $value): void
    {
        // void
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && in_array($offset, ['slug', 'id', 'count', 'post', 'featureImage']);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'slug' => $this->getSlug(),
            'id' => $this->getId(),
            'count' => $this->getCount(),
            'post' => $this->getPost(),
            'featureImage' => $this->getFeatureImage(),
            default => null,
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
    }

    public function offsetUnset(mixed $offset): void
    {
    }
}
