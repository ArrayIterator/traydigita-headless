<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use DateInterval;
use DateTimeInterface;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use SensitiveParameter;
use Throwable;
use TrayDigita\WP\Headless\Resource\Components\Dependencies\Tokenizer\AuthenticatorPayload;
use TrayDigita\WP\Headless\Resource\Lib\DatetimeImmutableUnit;
use TrayDigita\WP\Headless\Resource\Lib\Duration;
use TrayDigita\WP\Headless\Resource\Networks\ClientBrowserType;
use TrayDigita\WP\Headless\Resource\Networks\UserAgent;
use TrayDigita\WP\Headless\Resource\Utils\Random;
use function bin2hex;
use function get_current_user_id;
use function hash_hmac;
use function is_string;
use function pack;
use function strlen;
use function substr;
use function trim;

class StatelessAuthenticator
{
    public const LENGTH = 400; // 400 characters (200 bytes) in hex representation

    public const MIN_EXPIRATION = 15; // 15 seconds

    public const DEFAULT_EXPIRATION = 3600; // 1 hour

    public const SEPARATOR = ':'; // Combined Key Separator

    private string $keySeparator = StatelessTokenizer::SEPARATOR;

    /**
     * @var Duration $defaultExpiration The default expiration duration
     */
    public readonly Duration $defaultExpiration;

    /**
     * @var Duration $minExpiration The minimum expiration duration
     */
    public readonly Duration $minExpiration;

    /**
     * @var string $saltBinary The salt binary from the auth salt
     */
    private string $saltBinary;

    /**
     * StatelessAuthenticator constructor.
     *
     * @param KeyStorage $keyStorage The key storage instance
     * @param UserAgent $userAgent The user agent instance
     * @param User $user The user instance
     * @param string $keySeparator The key separator (default is ':')
     */
    public function __construct(
        #[SensitiveParameter]
        public readonly KeyStorage $keyStorage,
        public readonly UserAgent $userAgent,
        public readonly User $user,
        string $keySeparator = self::SEPARATOR
    ) {
        $this->setKeySeparator($keySeparator);
        $this->defaultExpiration = new Duration(seconds: self::DEFAULT_EXPIRATION, immutable: true);
        $this->minExpiration = new Duration(seconds: self::MIN_EXPIRATION, immutable: true);
    }

    /**
     * Get the value of keySeparator
     *
     * @return string
     */
    public function getKeySeparator(): string
    {
        return $this->keySeparator;
    }

    /**
     * Set the value of keySeparator
     *
     * @param string $keySeparator
     * @return void
     */
    public function setKeySeparator(string $keySeparator): void
    {
        $keySeparator = trim($keySeparator);
        if ($keySeparator !== '') {
            $this->keySeparator = $keySeparator;
        }
    }

    /**
     * Get the combined key from auth key and auth salt
     *
     * @return string
     */
    public function getCombinedKey(): string
    {
        return $this->keyStorage->getAuthKey() . $this->getKeySeparator() . $this->keyStorage->getAuthSalt();
    }

    /**
     * Hash the given data with the given key using HMAC-SHA256
     *
     * @param string $data
     * @param string $key
     * @param bool $raw
     * @return string
     */
    public function hash(string $data, string $key, bool $raw = false): string
    {
        return hash_hmac('sha256', $data, $key, $raw);
    }

    /**
     * Generate a random string of the given length
     *
     * @param int $length
     * @return string
     */
    public function genRandom(int $length): string
    {
        return Random::bytes($length);
    }

    /**
     * Get the current timestamp in milliseconds
     *
     * @return int
     */
    public function getTimestampMillisecond(): int
    {
        return DatetimeImmutableUnit::now()->toMilliSeconds();
    }

    /**
     * Concatenate multiple values into a single string with separator
     *
     * @param int|float|string ...$args
     * @return string
     */
    public function concatSeparator(int|float|string ...$args): string
    {
        return implode($this->getKeySeparator(), $args);
    }

    /**
     * Concatenate multiple values into a single string
     *
     * @param int|float|string ...$args
     * @return string
     */
    public function concat(int|float|string ...$args): string
    {
        return implode('', $args);
    }

    /**
     * Get the salt binary from the auth salt
     *
     * @return string
     */
    public function getSaltBinary(): string
    {
        return $this->saltBinary ??= $this->hash(
            $this->keyStorage->getAuthSalt(),
            $this->getCombinedKey(),
            true
        );
    }

    /**
     * Hash the given data with the given random, user and timestamp using HMAC-SHA256
     *
     * @param string $data
     * @param string $random
     * @param User $user
     * @param int $timestamp
     * @return string
     */
    public function hashInner(
        string $data,
        string $random,
        User $user,
        int $timestamp
    ): string {
        $key = $this->concatSeparator(
            $random,
            $this->getCombinedKey(),
            $user->username,
            $user->id,
            $timestamp
        );
        return $this->hash($data, $key, true);
    }

    /**
     * Hash the given data with the given random and timestamp using HMAC-SHA256
     *
     * @param string $data
     * @param string $random
     * @param int $timestamp
     * @return string
     */
    public function hashOuter(
        string $data,
        string $random,
        int $timestamp
    ): string {
        $key = $this->concat(
            $random,
            $this->getSaltBinary(),
            $timestamp
        );
        return $this->hash($data, $key, true);
    }

    /**
     * Compare two hashes in a timing-attack safe manner
     *
     * @param string $hash1
     * @param string $hash2
     * @return bool
     */
    public function hashEqual(string $hash1, string $hash2): bool
    {
        return $hash1 && $hash2 && hash_equals($hash1, $hash2);
    }

    /**
     * Create a Duration object from the given duration
     *
     * @param DateInterval|Duration|DateTimeInterface|null $duration
     * @return Duration
     */
    public function createDuration(DateInterval|Duration|DateTimeInterface|null $duration = null): Duration
    {
        if ($duration instanceof DateInterval) {
            $duration = Duration::fromDateInterval($duration, true);
        } elseif ($duration instanceof DateTimeInterface) {
            $duration = Duration::fromDate($duration, true);
        } elseif (!$duration instanceof Duration) {
            $duration = $this->defaultExpiration;
        }
        return $duration->lessThan($this->minExpiration) ? $this->minExpiration : $duration->asImmutable();
    }

    /**
     * Get the path and query from the URI
     *
     * @param UriInterface|RequestInterface|UserAgent|string|null $uri
     * @return string
     */
    public function pathUri(UriInterface|RequestInterface|UserAgent|string|null $uri = null): string
    {
        if ($uri instanceof UserAgent) {
            $uri = $uri->request->getUri();
        }
        $path = $uri ?? '';
        if (!$uri) {
            return $path;
        }
        if ($uri instanceof RequestInterface) {
            $uri = $uri->getUri();
        }
        if (is_string($uri)) {
            try {
                $uri = new Uri($uri);
            } catch (Throwable) {
                return $path;
            }
        }
        if ($uri instanceof UriInterface) {
            $path = $uri->getPath();
            $query = $uri->getQuery();
            if ($query !== '') {
                $path .= '?' . $query;
            }
        }
        return $path;
    }

    /**
     * Create a path signature for the given URI
     *
     * @param string $random
     * @param int $timestamp
     * @param UriInterface|ServerRequestInterface|UserAgent|string|null $uri
     * @return string
     */
    public function createPathSignature(
        string $random,
        int $timestamp,
        UriInterface|ServerRequestInterface|UserAgent|string|null $uri = null
    ): string {
        $path = $this->pathUri($uri);
        return $this->hashOuter($path, $random, $timestamp);
    }

    /**
     * Create a browser signature for the given browser type
     *
     * @param string $random
     * @param int $timestamp
     * @param string|UserAgent|ClientBrowserType|ServerRequestInterface $browserType
     * @return string
     */
    public function createBrowserSignature(
        string $random,
        int $timestamp,
        string|UserAgent|ClientBrowserType|ServerRequestInterface $browserType
    ): string {
        if ($browserType instanceof ServerRequestInterface) {
            $browserType = new UserAgent($browserType);
        }
        if ($browserType instanceof UserAgent) {
            $browserType = $browserType->clientBrowserType;
        }
        if ($browserType instanceof ClientBrowserType) {
            $browserType = $browserType->getBrowserType();
        }
        return $this->hashOuter($browserType, $random, $timestamp);
    }

    /**
     * Generate Authenticator Payload
     *
     * @param mixed $user if null, will use current user id
     * @param UriInterface|ServerRequestInterface|UserAgent|string|null $uri
     * @param DateInterval|Duration|null $duration
     * @return AuthenticatorPayload
     */
    public function generate(
        mixed $user = null,
        UriInterface|ServerRequestInterface|UserAgent|string|null $uri = null,
        DateInterval|Duration $duration = null
    ): AuthenticatorPayload {
        $userAgent = $this->userAgent;
        if (!$userAgent) {
            if ($uri instanceof ServerRequestInterface) {
                $userAgent = new UserAgent($uri);
            } elseif ($uri instanceof UserAgent) {
                $userAgent = $uri;
            }
        }
        $user ??= get_current_user_id();
        $user = $this->user->findOrEmpty($user);
        $random = $this->genRandom(16);
        $userId = $user->id;
        $timestamp = $this->getTimestampMillisecond();
        $expired = $this->createDuration($duration)->toDate()->toMilliSeconds();
        $user64 = pack('J', $userId);
        $timestamp64 = pack('J', $timestamp);
        $duration = pack('J', $expired);

        $pathSignature = $this->createPathSignature($random, $timestamp, $uri);
        $browserSignature = $this->createBrowserSignature($random, $timestamp, $userAgent);
        $idBinding = $this->hashInner($user64, $random, $user, $timestamp);
        $payload = $this->concat(
            $random,
            $pathSignature,
            $idBinding,
            $timestamp64,
            $duration,
            $user64,
            $browserSignature
        );
        $signature = $this->hashOuter($payload, $random, $timestamp);
        $token = bin2hex($this->concat($payload, $signature, $browserSignature));
        return new AuthenticatorPayload(
            $this,
            $user,
            $token,
            $payload,
            $signature,
            $browserSignature,
            $pathSignature,
            $random,
            $timestamp,
            $expired
        );
    }

    public function parse(string|AuthenticatorPayload $token): ?AuthenticatorPayload
    {
        $token = $token instanceof AuthenticatorPayload ? $token->token : $token;
        if (strlen($token) !== self::LENGTH) {
            return null;
        }

        $tokenBin = hex2bin($token);
        if ($tokenBin === false) {
            return null;
        }

        $browserSignature = substr($tokenBin, -32); // browser signature is the last 32 bytes
        $signature = substr($tokenBin, -64, 32); // signature is the second-to-last 32 bytes
        $payload = substr($tokenBin, 0, -64); // payload is the rest
        $random = substr($payload, 0, 16);
        $pathSignature = substr($payload, 16, 32);
        $comparedBrowserSignature = substr($payload, -32);
        // check browser signature first, if it doesn't match, return null
        if (!$this->hashEqual($browserSignature, $comparedBrowserSignature)) {
            return null;
        }
        $idBinding = substr($payload, 48, 32);
        $ts64 = substr($payload, 80, 8);
        $duration = substr($payload, 88, 8);
        $u64 = substr($payload, 96, 8);
        $userId = unpack('J', $u64)[1] ?? null;
        if ($userId === null || $userId < 0) { // minimum user is 0
            return null;
        }
        $timestamp = unpack('J', $ts64)[1] ?? 0;
        $expired = unpack('J', $duration)[1] ?? 0;
        $tokenSign = $this->hashOuter($payload, $random, $timestamp);
        if (!$this->hashEqual($signature, $tokenSign)) {
            return null;
        }
        $user = $this->user->findOrEmpty($userId);
        if ($user === null) {
            return null;
        }
        $idBindingSign = $this->hashInner($u64, $random, $user, $timestamp);
        if (!$this->hashEqual($idBinding, $idBindingSign)) {
            return null;
        }
        return new AuthenticatorPayload(
            $this,
            $user,
            $token,
            $payload,
            $signature,
            $browserSignature,
            $pathSignature,
            $random,
            $timestamp,
            $expired
        );
    }
}
