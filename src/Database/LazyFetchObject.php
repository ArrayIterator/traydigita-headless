<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Database;

use AllowDynamicProperties;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Serializable;
use Traversable;
use function array_key_exists;
use function debug_backtrace;
use function gettype;
use function is_string;
use function serialize;
use function strtolower;
use function unserialize;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

/**
 * @template TResult of array<non-empty-string, mixed>
 */
#[AllowDynamicProperties]
class LazyFetchObject implements IteratorAggregate, ArrayAccess, Serializable, JsonSerializable
{
    /**
     * @var TResult
     */
    private array $data;

    private array $new_data;

    protected array $blacklisted_key;

    private function __construct()
    {
        // void
    }

    public function has(string $name): bool
    {
        return isset($this->all()[strtolower($name)]);
    }

    public function set(string $name, mixed $value) : void
    {
        $name = strtolower($name);
        if (!isset($this->data) || !array_key_exists($name, $this->data)) {
            return;
        }
        $oldVal = $this->data[$name];
        $value = $this->setupNewValue($name, $value, $oldVal, $found);
        if (!$found) {
            return;
        }
        $this->new_data[$name] = $value;
    }

    public function get(string $name)
    {
        return $this->all()[strtolower($name)];
    }

    protected function getBlackListedKey() : array
    {
        return $this->blacklisted_key??[];
    }

    final public function __set(string $name, $value): void
    {
        $obj = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)[0] ?? [];
        if ($obj['file'] ?? null) {
            $this->set($name, $value);
            return;
        }
        $this->data ??= [];
        $lower = strtolower($name);
        $this->data[$lower] ??= $this->convertValue($lower, $value, $name);
    }

    protected function convertValue(string $name, mixed $value, string $realName)
    {
        return $value;
    }

    protected function setupNewValue(string $name, mixed $value, mixed $oldValue, bool &$canBeSet = null)
    {
        $canBeSet = $value !== $oldValue && gettype($value) === gettype($oldValue);
        return $value;
    }

    public function all(): array
    {
        return $this->data ?? [];
    }

    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    public function __unset(string $name): void
    {
        if (!isset($this->new_data)) {
            return;
        }
        unset($this->new_data[$name]);
        // void
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        if (!is_string($offset)) {
            return false;
        }
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!is_string($offset)) {
            return null;
        }
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->__unset($offset);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function serialize(): ?string
    {
        return serialize($this->toArray());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data, ['allowed_classes' => false]));
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @return TResult
     */
    public function jsonSerialize(): array
    {
        $result = $this->toArray();
        foreach ($this->getBlackListedKey() as $item) {
            if (!is_string($item)) {
                continue;
            }
            if (array_key_exists($item, $result)) {
                unset($result[$item]);
            }
        }
        return $result;
    }
}
