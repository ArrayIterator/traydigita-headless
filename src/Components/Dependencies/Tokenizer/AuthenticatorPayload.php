<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Tokenizer;

use DateInterval;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use SensitiveParameter;
use Stringable;
use TrayDigita\WP\Headless\Resource\Attributes\SensitiveData;
use TrayDigita\WP\Headless\Resource\Components\StatelessAuthenticator;
use TrayDigita\WP\Headless\Resource\Components\User;
use TrayDigita\WP\Headless\Resource\Lib\Duration;
use TrayDigita\WP\Headless\Resource\Networks\ClientBrowserType;
use TrayDigita\WP\Headless\Resource\Networks\UserAgent;

/**
 * @property-read bool $expired
 * @property-read Duration $expirationDuration
 * @mixin StatelessAuthenticator
 */
final class AuthenticatorPayload implements Stringable
{
    /**
     * @var bool
     */
    private bool $expired;

    /**
     * @var Duration
     */
    private Duration $expirationDuration;

    /**
     * @param StatelessAuthenticator $authenticator
     * @param User $user
     * @param string $token
     * @param string $payload
     * @param string $random
     * @param string $signature
     * @param string $browserSignature
     * @param string $pathSignature
     * @param int $timestamp milliseconds
     * @param int $expiration milliseconds
     */
    public function __construct(
        public readonly StatelessAuthenticator $authenticator,
        #[SensitiveParameter]
        #[SensitiveData('This parameter contain sensitive information about user')]
        public readonly User $user,
        public readonly string $token,
        public readonly string $payload,
        public readonly string $signature,
        public readonly string $browserSignature,
        public readonly string $pathSignature,
        public readonly string $random,
        public readonly int $timestamp, // milliseconds
        public readonly int $expiration // milliseconds
    ) {
    }

    /**
     * Get the payload as a string or hex representation
     *
     * @param bool $hex Whether to return the payload in hex format
     * @return string The payload as a string or hex representation
     */
    public function getPayload(bool $hex = false): string
    {
        return $hex ? bin2hex($this->payload) : $this->payload;
    }

    /**
     * Get the signature as a string or hex representation
     *
     * @param bool $hex Whether to return the signature in hex format
     * @return string The signature as a string or hex representation
     */
    public function getSignature(bool $hex = false): string
    {
        return $hex ? bin2hex($this->signature) : $this->signature;
    }

    /**
     * Get the browser signature as a string or hex representation
     *
     * @param bool $hex Whether to return the browser signature in hex format
     * @return string The browser signature as a string or hex representation
     */
    public function getBrowserSignature(bool $hex = false): string
    {
        return $hex ? bin2hex($this->browserSignature) : $this->browserSignature;
    }

    /**
     * Check if the browser signature matches the given browser type
     *
     * @param string|UserAgent|ClientBrowserType|ServerRequestInterface $browserType
     * @return bool
     */
    public function isBrowserMatch(string|UserAgent|ClientBrowserType|ServerRequestInterface $browserType): bool
    {
        return $this->authenticator->hash->equals(
            $this->browserSignature,
            $this->createBrowserSignature($this->random, $this->timestamp, $browserType)
        );
    }

    public function isPathMatch(RequestInterface|UserAgent|string|null $uri = null): bool
    {
        return $this->authenticator->hash->equals(
            $this->pathSignature,
            $this->createPathSignature($this->random, $this->timestamp, $uri)
        );
    }

    /**
     * Check if the payload is expired after the given duration
     *
     * @param Duration|DateInterval|string|int|float $duration
     * @return bool
     */
    public function expiresAfter(Duration|DateInterval|string|int|float $duration): bool
    {
        return $this->getExpirationDuration()->lessThan($duration);
    }

    /**
     * Get the expiration duration as a Duration object
     *
     * @return Duration
     */
    public function getExpirationDuration(): Duration
    {
        return $this->expirationDuration ??= Duration::fromMilliseconds($this->expiration);
    }

    /**
     * Check if the payload is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expired ??= $this->getExpirationDuration()->lessThan(Duration::now());
    }

    /**
     * Magic method to call methods on the underlying authenticator
     *
     * @param string $name The method name
     * @param array $arguments The method arguments
     * @return mixed The result of the method call
     */
    public function __call(string $name, array $arguments)
    {
        return $this->authenticator->$name(...$arguments);
    }

    public function __get(string $name)
    {
        if ($name === 'expired') {
            return $this->isExpired();
        }
        if ($name === 'expirationDuration') {
            return $this->getExpirationDuration();
        }
        return $this->authenticator->$name ?? null;
    }

    public function __set(string $name, $value): void
    {
        // void
    }

    public function __isset(string $name): bool
    {
        if ($name === 'expired' || $name === 'expirationDuration') {
            return true;
        }
        return isset($this->authenticator->$name);
    }

    public function __unset(string $name): void
    {
    }

    /**
     * Convert the payload to a string representation (the token)
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->token;
    }
}
