<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Analytics\Records;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Serializable;
use Traversable;
use function count;
use function is_array;
use function is_int;
use function is_string;
use function serialize;
use function strtotime;
use function time;
use function uasort;
use function unserialize;

/**
 * @template-implements IteratorAggregate<string, RecordAnalytic>
 */
final class CollectionRecordsAnalytics implements
    IteratorAggregate,
    ArrayAccess,
    Countable,
    Serializable,
    JsonSerializable
{
    /**
     * @var int
     */
    protected int $timestamp;

    /**
     * @var array<string, RecordAnalytic>
     */
    protected array $data;

    /**
     * @var string
     */
    protected string $property_id;

    private static int $the_1990_time;

    public function __construct(string $property_id, int $timestamp, RecordAnalytic ...$records)
    {
        $data = [];
        foreach ($records as $record) {
            $data[$record->getSlug()] = $record;
        }
        // sort by count
        uasort($data, function (RecordAnalytic $a, RecordAnalytic $b) {
            return $b->count() <=> $a->count();
        });
        $this->property_id = $property_id;
        $this->timestamp = $timestamp;
        $this->data = $data;
    }

    private static function get1990Time(): int
    {
        return self::$the_1990_time ??= strtotime('1990-01-01 01:01:01');
    }

    public static function filterMap(mixed $data): ?array
    {
        if (!is_array($data) || !is_int($data['timestamp']) || $data['timestamp'] < self::get1990Time()) {
            return null;
        }
        $propertyId = $data['property_id'] ?? null;
        if (!is_string($propertyId)) {
            return null;
        }
        $data = $data['data'] ?? null;
        if (!is_array($data)) {
            return null;
        }
        foreach ($data as $key => $i) {
            if (is_array($i)) {
                return null;
            }
            if (!is_string($key)) {
                unset($data[$key]);
                continue;
            }
            if (!is_int($i['count'] ?? null)
                || !is_int($i['id'] ?? null)
                || ($i['slug'] ?? null !== $key)
            ) {
                return null;
            }
            $data[$key] = [
                'id' => $i['id'],
                'count' => $i['count'],
                'slug' => $i['slug'],
            ];
        }
        return $data;
    }

    /**
     * @return array{
     *     property_id: string,
     *     timestamp: int,
     *     data: array<string, RecordAnalytic>
     * }
     */
    public function toArray(): array
    {
        return [
            'property_id' => $this->getPropertyId(),
            'timestamp' => $this->getTimestamp(),
            'data' => $this->getData()
        ];
    }

    public function isExpire(int $hour = 24): bool
    {
        $cTime = time();
        $expireTime = $cTime - ($hour * \HOUR_IN_SECONDS);
        return ($this->timestamp - $expireTime) < 0
            // futures
            || $cTime < $this->timestamp;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getPropertyId(): string
    {
        return $this->property_id;
    }

    /**
     * @return Traversable<string, RecordAnalytic>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }
        return isset($this->data[$offset]);
    }

    public function offsetGet(mixed $offset) : mixed
    {
        return !is_string($offset) ? null : ($this->data[$offset] ?? null);
    }

    public function offsetSet(mixed $offset, mixed $value) : void
    {
        // void
    }

    public function offsetUnset(mixed $offset) : void
    {
        // void
    }

    public function count(): int
    {
        return count($this->data);
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $data = self::filterMap(unserialize($data));
        $this->__unserialize($data);
    }

    public function __unserialize(array $data): void
    {
        $this->data = $data['data'];
        $this->timestamp = $data['timestamp'];
        $this->property_id = $data['property_id'];
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function mergeWith(self $records, int $limitUntil = 50): self
    {
        $obj = clone $this;
        if ($records === $this) {
            return $obj;
        }
        $count = count($this->data);
        if ($count >= $limitUntil) {
            return $obj;
        }
        foreach ($records->data as $key => $item) {
            if (isset($obj->data[$key])) {
                continue;
            }
            if ($count++ >= $limitUntil) {
                break;
            }
            $obj->data[$key] = $item;
        }
        // sort by count
        uasort($obj->data, function (RecordAnalytic $a, RecordAnalytic $b) {
            return $b->getCount() <=> $a->getCount();
        });
        return $obj;
    }

    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
}
