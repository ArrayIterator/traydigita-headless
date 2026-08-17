<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Lib;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Stringable;
use function method_exists;
use function wp_timezone;

class DatetimeImmutableUnit extends DateTimeImmutable implements Stringable
{
    /**
     * @var DateTimeZone
     */
    private static DateTimeZone $utcTimeZone;

    /**
     * @var DateTimeZone
     */
    private static DateTimeZone $wpTimeZone;

    /**
     * @return DateTimeZone
     */
    public static function utcTimeZone(): DateTimeZone
    {
        return self::$utcTimeZone ??= new DateTimeZone('UTC');
    }

    /**
     * @return int
     */
    public function toMilliSeconds(): int
    {
        return (int)($this->format('Uv'));
    }

    /**
     * @return int
     */
    public function toMicroseconds(): int
    {
        return (int)($this->format('Uu'));
    }

    /**
     * @return DateTimeZone
     */
    public static function wpTimezone(): DateTimeZone
    {
        return self::$wpTimeZone ??= wp_timezone();
    }

    /**
     * @throws Exception
     */
    public static function wrap(DateTimeImmutable $datetime): static
    {
        return new static($datetime->format('c'), $datetime->getTimezone());
    }

    /**
     * @return static
     */
    public static function now(): static
    {
        return new static();
    }

    /**
     * @param $format
     * @param $datetime
     * @param $timezone
     * @return static
     * @throws Exception
     */
    public static function createFromFormat($format, $datetime, $timezone = null): static
    {
        $datetime = parent::createFromFormat($format, $datetime, $timezone);
        if (!$datetime) {
            throw new Exception('Failed to create DateTimeImmutable from format: ' . $format);
        }
        return static::wrap($datetime);
    }

    /**
     * @param DateTime $object
     * @return static
     * @throws Exception
     */
    public static function createFromMutable(DateTime $object): static
    {
        return static::wrap(parent::createFromMutable($object));
    }

    /**
     * @param DateTimeInterface $object
     * @return static
     * @noinspection PhpDocMissingThrowsInspection
     */
    public static function createFromInterface(DateTimeInterface $object): static
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return static::wrap(parent::createFromInterface($object));
    }

    /**
     * Creates a new instance of the class from a database datetime string.
     *
     * @param string $datetime
     * @param DateTimeZone|null $timezone
     * @return static
     * @throws Exception
     */
    public static function createFromDatabase(string $datetime, ?DateTimeZone $timezone = null): static
    {
        return static::createFromFormat('Y-m-d H:i:s', $datetime, $timezone);
    }

    /**
     * Creates a new instance of the class from a WordPress database datetime string.
     *
     * @param string $datetime
     * @return static
     * @throws Exception
     */
    public static function createFromWordPressDatabase(string $datetime): static
    {
        return static::createFromFormat('Y-m-d H:i:s', $datetime, self::wpTimezone());
    }

    /**
     * Creates a new instance of the class from a timestamp.
     *
     * @param float|int $timestamp
     * @return static
     * @throws Exception
     */
    public static function createFromTimestamp(float|int $timestamp): static
    {
        if (method_exists(parent::class, 'createFromTimestamp')) {
            return self::wrap(parent::createFromTimestamp($timestamp));
        }
        return new static('@' . (int)$timestamp, self::utcTimeZone());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }
}
