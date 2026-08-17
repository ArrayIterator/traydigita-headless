<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Data;

use SensitiveParameter;
use Serializable;
use TrayDigita\WP\Headless\Resource\Attributes\SensitiveData;
use function serialize;
use function time;
use function unserialize;

/**
 * @template T of mixed
 * @property-read  string $name
 * @property-read T $value
 * @property-read int $expiration
 * @property-read bool $expired
 */
#[SensitiveData('Transient data may contain sensitive information')]
final class TransientData implements Serializable
{
    /**
     * @var bool $expired Indicates whether the transient data has expired
     */
    protected bool $expired;

    /**
     * TransientData constructor.
     *
     * @param string $name The name of the transient data
     * @param T $value The value of the transient data
     * @param int $expiration The expiration time in seconds
     */
    public function __construct(
        private string $name,
        #[SensitiveParameter]
        #[SensitiveData('This parameter may contain sensitive information')]
        private mixed $value,
        private int $expiration
    ) {
        $this->expired = $this->expiration > 0 && time() > $this->expiration;
        if ($this->expired) {
            $this->value = null;
        }
    }

    /**
     * @return string The name of the transient data
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return T The value of the transient data
     */
    #[SensitiveData('This method may returns sensitive data')]
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @return int The expiration time in seconds
     */
    public function getExpiration(): int
    {
        return $this->expiration;
    }

    /**
     * @return bool Indicates whether the transient data has expired
     */
    public function isExpired(): bool
    {
        return $this->expired;
    }

    /**
     * Creates a new instance of TransientData with the specified expiration time.
     *
     * @param int $expiration The new expiration time in seconds
     * @return self A new instance of TransientData with the updated expiration time
     */
    public function withExpiration(int $expiration): self
    {
        if ($expiration !== 0 && ($expiration + 30) < time()) {
            $expiration += time();
        }
        $obj = clone $this;
        $obj->{'expiration'} = $expiration;
        $obj->{'expired'} = $expiration > 0 && time() > $expiration;
        if ($obj->expired) {
            $obj->{'value'} = null;
        }
        return $obj;
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data) : void
    {
        $this->__unserialize(unserialize($data));
    }

    #[SensitiveData('This method returns sensitive data')]
    public function __serialize(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'expiration' => $this->expiration,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->value = $data['value'];
        $this->expiration = (int)$data['expiration'];
        $this->expired = $this->expiration > 0 && time() > $this->expiration;
    }
}
