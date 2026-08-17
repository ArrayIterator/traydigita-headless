<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use SensitiveParameter;
use TrayDigita\WP\Headless\Resource\Components\Dependencies\Tokenizer\StatelessPayload;
use TrayDigita\WP\Headless\Resource\Utils\Random;

class StatelessTokenizer
{
    public const LENGTH = 208; // 104 bytes * 2 (hex)

    public const SEPARATOR = ':'; // Combined Key Separator

    public const MIN_EXPIRATION = 30; // 30 seconds

    public const DEFAULT_EXPIRATION = 3600; // 1 hour

    private string $separator = self::SEPARATOR;

    private int $expiration = self::DEFAULT_EXPIRATION;

    public function __construct(
        #[SensitiveParameter]
        public readonly KeyStorage $keyStorage,
        int $expiration = self::DEFAULT_EXPIRATION,
        string $separator = self::SEPARATOR
    ) {
        $this->setSeparator($separator);
        $this->setExpiration($expiration);
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSeparator(string $separator): void
    {
        $separator = trim($separator);
        if ($separator !== '') {
            $this->separator = $separator;
        }
    }

    public function getExpiration(): int
    {
        return $this->expiration;
    }

    public function setExpiration(int $expiration): void
    {
        $this->expiration = max($expiration, self::MIN_EXPIRATION);
    }

    public function getCombinedKey(): string
    {
        return $this->keyStorage->getAuthKey() . $this->getSeparator() . $this->keyStorage->getAuthSalt();
    }

    /**
     * Generate Session Token
     *
     * @param int $id
     * @param ?int $durationSecs
     * @return StatelessPayload
     */
    public function generate(int $id, ?int $durationSecs = null): StatelessPayload
    {
        $now = time();
        $expiredAt = $now + ($durationSecs ?? $this->getExpiration());

        // 1. Generate Random 16 bytes (like UUID v4 bytes)
        $random16 = Random::bytes(16);

        // 2. Prepare Big-Endian Bytes
        $user8 = pack('J', $id); // 'J' = unsigned long 64-bit big-endian
        $time8 = pack('J', $now);
        $exp8 = pack('J', $expiredAt);

        // using combined key for inner
        $combinedKey = $this->getCombinedKey();
        // 3. Inner Signature (Identity Binding) - Just Sign The UserID!
        $idBinding = hash_hmac('sha256', $user8, $combinedKey, true);

        // 4. Construct Buffer (72 bytes payload)
        // [0..16] random + [16..48] idBinding + [48..56] time + [56..64] exp + [64..72] user
        $payloadPart = $random16 . $idBinding . $time8 . $exp8 . $user8;

        // 5. Final Outer Sign (32 bytes signature) - using salt, can be used as public verification
        $finalSign = hash_hmac('sha256', $payloadPart, $this->keyStorage->getAuthSalt(), true);

        // 6. Final Token (104 bytes -> 208 hex chars)
        $tokenHex = bin2hex($payloadPart . $finalSign);

        return new StatelessPayload($tokenHex, $id, $now, $expiredAt, $random16);
    }

    /**
     * Parse Token Hex to SessionPayload
     *
     * @param string $token the token hex string
     * @return StatelessPayload|null returns SessionPayload if valid, null if invalid
     */
    public function parse(string $token): ?StatelessPayload
    {
        if (strlen($token) !== self::LENGTH) {
            return null; // throw Exception? fixed length!!
        }

        $bytes = hex2bin($token);
        if (!$bytes) {
            return null; // false mean nothing! early return
        }

        // Split Buffer
        $payloadPart = substr($bytes, 0, 72);
        $outerSignature = substr($bytes, 72, 32);

        // 1. Verify Outer Signature - Using salt
        $expectedOuterSign = hash_hmac('sha256', $payloadPart, $this->keyStorage->getAuthSalt(), true);
        if (!hash_equals($expectedOuterSign, $outerSignature)) {
            return null; // Integrity check failed
        }

        // 2. Extract Components
        $random16 = substr($payloadPart, 0, 16);
        $innerSignature = substr($payloadPart, 16, 32);

        // Unpack Big-Endian
        $timestamp = unpack('J', substr($payloadPart, 48, 8))[1];
        $expiredAt = unpack('J', substr($payloadPart, 56, 8))[1];
        $user8 = substr($payloadPart, 64, 8);
        $userId = unpack('J', $user8)[1];

        $combinedKey = $this->getCombinedKey();
        // 3. Verify Inner Signature
        $expectedInnerSign = hash_hmac('sha256', $user8, $combinedKey, true);
        if (!hash_equals($expectedInnerSign, $innerSignature)) {
            return null; // Identity binding failed
        }

        return new StatelessPayload($token, $userId, $timestamp, $expiredAt, $random16);
    }
}
