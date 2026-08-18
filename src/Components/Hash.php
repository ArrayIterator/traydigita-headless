<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use SensitiveParameter;
use TrayDigita\WP\Headless\Resource\Attributes\SensitiveData;
use function hash;
use function hash_equals;
use function hash_hmac;

#[SensitiveData('Hash contain key storage')]
final class Hash
{
    public const MD5 = 'md5';
    public const SHA1 = 'sha1';
    public const SHA256 = 'sha256';
    public const SHA384 = 'sha384';
    public const SHA512 = 'sha512';

    /**
     * @param KeyStorage $keyStorage
     */
    public function __construct(
        #[SensitiveParameter]
        public readonly KeyStorage $keyStorage
    ) {
    }

    /**
     * Generate hash
     *
     * @param string $algo
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function hash(string $algo, string $data, bool $rawOutput = false): string
    {
        return hash($algo, $data, $rawOutput);
    }

    /**
     * Generate MD5 hash
     *
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function md5(string $data, bool $rawOutput = false): string
    {
        return $this->hash(self::MD5, $data, $rawOutput);
    }

    /**
     * Generate SHA1 hash
     *
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function sha1(string $data, bool $rawOutput = false): string
    {
        return $this->hash(self::SHA1, $data, $rawOutput);
    }

    /**
     * Generate SHA256 hash
     *
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function sha256(string $data, bool $rawOutput = false): string
    {
        return $this->hash(self::SHA256, $data, $rawOutput);
    }

    /**
     * Generate SHA384 hash
     *
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function sha384(string $data, bool $rawOutput = false): string
    {
        return $this->hash(self::SHA384, $data, $rawOutput);
    }

    /**
     * Generate SHA512 hash
     *
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function sha512(string $data, bool $rawOutput = false): string
    {
        return $this->hash(self::SHA512, $data, $rawOutput);
    }

    /**
     * Generate HMAC hash
     *
     * @param string $algo
     * @param string $data
     * @param string $key
     * @param bool $rawOutput
     * @return string
     */
    public function hmac(
        string $algo,
        string $data,
        string $key,
        bool $rawOutput = false
    ): string {
        return hash_hmac($algo, $data, $key, $rawOutput);
    }

    /**
     * Generate HMAC hash using the auth salt
     *
     * @param string $algo
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function hmacSalt(string $algo, string $data, bool $rawOutput = false): string
    {
        return $this->hmac($algo, $data, $this->keyStorage->getAuthSalt(), $rawOutput);
    }

    /**
     * Generate HMAC hash using the auth key
     *
     * @param string $algo
     * @param string $data
     * @param bool $rawOutput
     * @return string
     */
    public function hmacKey(string $algo, string $data, bool $rawOutput = false): string
    {
        return $this->hmac($algo, $data, $this->keyStorage->getAuthKey(), $rawOutput);
    }

    /**
     * Generate HMAC hash using the combined key
     *
     * @param string $algo
     * @param string $data
     * @param bool $rawOutput
     * @param ?string $separator
     * @return string
     */
    public function hmacCombined(string $algo, string $data, bool $rawOutput = false, ?string $separator = null): string
    {
        return $this->hmac($algo, $data, $this->keyStorage->getCombinedKey($separator), $rawOutput);
    }

    /**
     * Generate HMAC MD5 hash
     *
     * @param string $data
     * @param string $key
     * @param bool $rawOutput
     * @return string
     */
    public function hmacMd5(string $data, string $key, bool $rawOutput = false): string
    {
        return $this->hmac(self::MD5, $data, $key, $rawOutput);
    }

    /**
     * Generate HMAC SHA1 hash
     *
     * @param string $data
     * @param string $key
     * @param bool $rawOutput
     * @return string
     */
    public function hmacSha1(string $data, string $key, bool $rawOutput = false): string
    {
        return $this->hmac(self::SHA1, $data, $key, $rawOutput);
    }

    /**
     * Generate HMAC SHA256 hash
     *
     * @param string $data
     * @param string $key
     * @param bool $rawOutput
     * @return string
     */
    public function hmacSha256(string $data, string $key, bool $rawOutput = false): string
    {
        return $this->hmac(self::SHA256, $data, $key, $rawOutput);
    }

    /**
     * Generate HMAC SHA384 hash
     *
     * @param string $data
     * @param string $key
     * @param bool $rawOutput
     * @return string
     */
    public function hmacSha384(string $data, string $key, bool $rawOutput = false): string
    {
        return $this->hmac(self::SHA384, $data, $key, $rawOutput);
    }

    /**
     * Generate HMAC SHA512 hash
     *
     * @param string $data
     * @param string $key
     * @param bool $rawOutput
     * @return string
     */
    public function hmacSha512(string $data, string $key, bool $rawOutput = false): string
    {
        return $this->hmac(self::SHA512, $data, $key, $rawOutput);
    }

    /**
     * Compare two hashes
     *
     * @param string $hash1
     * @param string $hash2
     * @return bool
     */
    public function equals(string $hash1, string $hash2): bool
    {
        return $hash1 && $hash2 && hash_equals($hash1, $hash2);
    }
}
