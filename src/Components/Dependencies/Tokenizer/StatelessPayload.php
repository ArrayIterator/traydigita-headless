<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Tokenizer;

use function time;

/**
 * StatelessPayload class represents a payload for stateless authentication tokens.
 */
class StatelessPayload
{
    /**
     * @var bool $expired
     */
    public readonly bool $expired;

    /**
     * @var int $time
     */
    public readonly int $time;

    public function __construct(
        public readonly string $token,
        public readonly int $userId,
        public readonly int $timestamp,
        public readonly int $expiredAt,
        public readonly string $random16 // binary string
    ) {
        $this->time = time();
        $this->expired = $this->timestamp <= 0 ||
            $this->expiredAt <= 0 ||
            $this->timestamp >= $this->time ||
            $this->expiredAt <= $this->time;
    }

    /**
     * Check if the token needs to be renewed based on the given renewal duration.
     *
     * @param int $renewDurationSecs
     * @return bool
     */
    public function isNeedRenew(int $renewDurationSecs): bool
    {
        return $this->expiredAt > $this->time && ($this->expiredAt - $this->time) <= $renewDurationSecs;
    }

    /**
     * Convert the StatelessPayload object to a string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->token;
    }
}
