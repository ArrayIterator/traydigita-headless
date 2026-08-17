<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Lib;

use ArrayAccess;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use Serializable;
use Stringable;
use function abs;
use function explode;
use function intdiv;
use function is_float;
use function is_int;
use function is_string;
use function round;
use function serialize;
use function str_pad;
use function strlen;
use function strtolower;
use function strtotime;
use function substr;
use function unserialize;

final class Duration implements JsonSerializable, Stringable, Serializable, ArrayAccess
{
    /**
     * Kept for backwards compatibility.
     *
     * This is not a limit for Duration::years because Duration is not
     * a calendar year.
     */
    public const MAX_YEARS = 9999;

    private int $microseconds = 0;

    private int $milliseconds = 0;

    private int $seconds = 0;

    private int $minutes = 0;

    private int $hours = 0;

    private int $days = 0;

    private int $months = 0;

    private int $years = 0;

    private bool $immutable;

    public function __construct(
        int $years = 0,
        int $months = 0,
        int $days = 0,
        int $hours = 0,
        int $minutes = 0,
        int $seconds = 0,
        int $milliseconds = 0,
        int $microseconds = 0,
        bool $immutable = false
    ) {
        /*
         * Keep the smallest unit first so every unit can carry into
         * the next unit.
         */
        $this
            ->addMicroseconds($microseconds)
            ->addMilliseconds($milliseconds)
            ->addSeconds($seconds)
            ->addMinutes($minutes)
            ->addHours($hours)
            ->addDays($days)
            ->addMonths($months)
            ->addYears($years);

        $this->immutable = $immutable;
    }

    public function isImmutable(): bool
    {
        return $this->immutable ?? false;
    }

    /**
     * Create a Duration from a millisecond timestamp.
     *
     * @param int $expiration Milliseconds
     */
    public static function fromMilliseconds(int $expiration): Duration
    {
        $negative = $expiration < 0;
        /*
         * Millisecond timestamp.
         */
        $milliseconds = $expiration % 1000;
        $timestamp = intdiv($expiration, 1000);
        $values = self::decomposeSeconds(
            $timestamp,
            $milliseconds,
            0
        );
        if ($negative) {
            $values = self::negateParts($values);
        }
        return new self(
            $values['years'],
            $values['months'],
            $values['days'],
            $values['hours'],
            $values['minutes'],
            $values['seconds'],
            $values['milliseconds'],
            $values['microseconds']
        );
    }

    public function asImmutable(bool $immutable = true): self
    {
        return new self(
            $this->years,
            $this->months,
            $this->days,
            $this->hours,
            $this->minutes,
            $this->seconds,
            $this->milliseconds,
            $this->microseconds,
            $immutable
        );
    }

    /**
     * Create a Duration from the current time.
     */
    public static function now() : self
    {
        return self::fromDateInterval(new DateInterval('PT0S'), true);
    }

    /**
     * Create a Duration from DateInterval.
     */
    public static function fromDateInterval(
        DateInterval $interval,
        bool $immutable = false
    ): self {
        /*
         * DateInterval::$f is a fraction of one second.
         *
         * Example:
         *   0.123456
         *
         * becomes:
         *   123 milliseconds
         *   456 microseconds
         */
        $microseconds = (int)round($interval->f * 1_000_000);

        /*
         * Floating point rounding can theoretically produce exactly
         * 1,000,000.
         */
        if ($microseconds >= 1_000_000) {
            $microseconds = 999_999;
        }

        $milliseconds = intdiv($microseconds, 1000);
        $microseconds %= 1000;

        $duration = new self(
            $interval->y,
            $interval->m,
            $interval->d,
            $interval->h,
            $interval->i,
            $interval->s,
            $milliseconds,
            $microseconds,
            false
        );

        /*
         * DateInterval uses invert instead of negative components.
         *
         * Convert the already-normalized positive duration into its
         * canonical negative representation.
         */
        if ($interval->invert) {
            $duration = $duration->negated();
        }

        if ($immutable) {
            return $duration->asImmutable();
        }

        return $duration;
    }

    public static function fromDate(
        DateTimeInterface $date,
        bool $immutable = false
    ): self {
        $now = new DateTimeImmutable();
        $interval = $now->diff($date);

        return self::fromDateInterval($interval, $immutable);
    }

    /**
     * Parse a numeric time representation.
     *
     * Integer:
     *   <= 10 digits  = seconds
     *   11-13 digits  = milliseconds
     *   > 13 digits   = microseconds
     *
     * Float:
     *   seconds + fractional seconds
     */
    private static function parseTime(int|float $time): array
    {
        if (is_float($time)) {
            $negative = $time < 0;
            $absolute = abs($time);

            $string = (string)$absolute;
            $parts = explode('.', $string, 2);

            $seconds = (int)$parts[0];

            $fraction = $parts[1] ?? '';
            $fraction = str_pad(substr($fraction, 0, 6), 6, '0');

            $microseconds = (int)$fraction;
            $milliseconds = intdiv($microseconds, 1000);
            $microseconds %= 1000;

            $values = self::decomposeSeconds(
                $seconds,
                $milliseconds,
                $microseconds
            );

            return $negative
                ? self::negateParts($values)
                : $values;
        }

        $negative = $time < 0;
        $absolute = abs($time);

        $length = strlen((string)$absolute);

        if ($length > 13) {
            /*
             * Microsecond timestamp.
             *
             * Example:
             * 123456789012345
             */
            $microseconds = $absolute % 1000;
            $milliseconds = intdiv($absolute, 1000) % 1000;
            $timestamp = intdiv($absolute, 1_000_000);

            $values = self::decomposeSeconds(
                $timestamp,
                $milliseconds,
                $microseconds
            );
        } elseif ($length > 10) {
            /*
             * Millisecond timestamp.
             */
            $milliseconds = $absolute % 1000;
            $timestamp = intdiv($absolute, 1000);

            $values = self::decomposeSeconds(
                $timestamp,
                $milliseconds,
                0
            );
        } else {
            /*
             * Second timestamp.
             */
            $values = self::decomposeSeconds(
                $absolute,
                0,
                0
            );
        }

        return $negative
            ? self::negateParts($values)
            : $values;
    }

    /**
     * Decompose positive seconds/ms/us into Duration fields.
     *
     * The resulting fields are canonical and non-negative.
     */
    private static function decomposeSeconds(
        int $seconds,
        int $milliseconds = 0,
        int $microseconds = 0
    ): array {
        $years = intdiv($seconds, 365 * 24 * 60 * 60);
        $seconds %= 365 * 24 * 60 * 60;

        $months = intdiv($seconds, 30 * 24 * 60 * 60);
        $seconds %= 30 * 24 * 60 * 60;

        $days = intdiv($seconds, 24 * 60 * 60);
        $seconds %= 24 * 60 * 60;

        $hours = intdiv($seconds, 60 * 60);
        $seconds %= 60 * 60;

        $minutes = intdiv($seconds, 60);
        $seconds %= 60;

        return [
            'microseconds' => $microseconds,
            'milliseconds' => $milliseconds,
            'seconds' => $seconds,
            'minutes' => $minutes,
            'hours' => $hours,
            'days' => $days,
            'months' => $months,
            'years' => $years,
        ];
    }

    /**
     * Convert canonical positive parts into canonical negative parts.
     *
     * Example:
     *   1 second
     * becomes:
     *   -1 minute + 59 seconds
     */
    private static function negateParts(array $parts): array
    {
        $duration = new self(
            $parts['years'],
            $parts['months'],
            $parts['days'],
            $parts['hours'],
            $parts['minutes'],
            $parts['seconds'],
            $parts['milliseconds'],
            $parts['microseconds']
        );

        $negative = $duration->negated();

        return [
            'microseconds' => $negative->microseconds,
            'milliseconds' => $negative->milliseconds,
            'seconds' => $negative->seconds,
            'minutes' => $negative->minutes,
            'hours' => $negative->hours,
            'days' => $negative->days,
            'months' => $negative->months,
            'years' => $negative->years,
        ];
    }

    public static function fromUnit(
        int|float $timestamp,
        bool $immutable = false
    ): self {
        $values = self::parseTime($timestamp);

        return new self(
            $values['years'],
            $values['months'],
            $values['days'],
            $values['hours'],
            $values['minutes'],
            $values['seconds'],
            $values['milliseconds'],
            $values['microseconds'],
            $immutable
        );
    }

    public static function fromString(
        string $durationString,
        bool $immutable = false
    ): self {
        $time = strtotime($durationString);

        if ($time === false) {
            return new self(immutable: $immutable);
        }

        return self::fromUnit($time, $immutable);
    }

    public static function from(
        DateInterval|DateTimeInterface|int|float|string $time,
        bool $immutable = false
    ): self {
        if ($time instanceof DateInterval) {
            return self::fromDateInterval($time, $immutable);
        }

        if ($time instanceof DateTimeInterface) {
            return self::fromDate($time, $immutable);
        }

        if (is_int($time) || is_float($time)) {
            /*
             * Do not cast float to int here. The fractional part is
             * intentionally handled by parseTime().
             */
            return self::fromUnit($time, $immutable);
        }

        if (is_string($time)) {
            return self::fromString($time, $immutable);
        }

        return new self(immutable: $immutable);
    }

    public function greaterThan(
        Duration|DateInterval|DateTimeInterface|int|float|string $other
    ): bool {
        $other = $other instanceof self
            ? $other
            : self::from($other);

        return $this->toMicrosecondsTime()
            > $other->toMicrosecondsTime();
    }

    public function lessThan(
        Duration|DateInterval|DateTimeInterface|int|float|string $other
    ): bool {
        $other = $other instanceof self
            ? $other
            : self::from($other);

        return $this->toMicrosecondsTime()
            < $other->toMicrosecondsTime();
    }

    public function equal(
        Duration|DateInterval|DateTimeInterface|int|float|string $other
    ): bool {
        $other = $other instanceof self
            ? $other
            : self::from($other);

        return $this->toMicrosecondsTime()
            === $other->toMicrosecondsTime();
    }

    public function toMicrosecondsTime(): int
    {
        return $this->microseconds
            + ($this->milliseconds * 1_000)
            + ($this->seconds * 1_000_000)
            + ($this->minutes * 60 * 1_000_000)
            + ($this->hours * 60 * 60 * 1_000_000)
            + ($this->days * 24 * 60 * 60 * 1_000_000)
            + ($this->months * 30 * 24 * 60 * 60 * 1_000_000)
            + ($this->years * 365 * 24 * 60 * 60 * 1_000_000);
    }

    public function toMillisecondsTime(): int
    {
        return $this->milliseconds
            + ($this->seconds * 1_000)
            + ($this->minutes * 60 * 1_000)
            + ($this->hours * 60 * 60 * 1_000)
            + ($this->days * 24 * 60 * 60 * 1_000)
            + ($this->months * 30 * 24 * 60 * 60 * 1_000)
            + ($this->years * 365 * 24 * 60 * 60 * 1_000);
    }

    public function toTime(): int
    {
        return $this->seconds
            + ($this->minutes * 60)
            + ($this->hours * 60 * 60)
            + ($this->days * 24 * 60 * 60)
            + ($this->months * 30 * 24 * 60 * 60)
            + ($this->years * 365 * 24 * 60 * 60);
    }

    /**
     * Convert to DateInterval while preserving the sign.
     */
    public function toDateInterval(): DateInterval
    {
        $negative = $this->toMicrosecondsTime() < 0;

        if (!$negative) {
            $dateIv = new DateInterval('P0Y0M0DT0H0M0S');

            $dateIv->y = $this->years;
            $dateIv->m = $this->months;
            $dateIv->d = $this->days;
            $dateIv->h = $this->hours;
            $dateIv->i = $this->minutes;
            $dateIv->s = $this->seconds;
            $dateIv->f = (
                    ($this->milliseconds * 1000) +
                    $this->microseconds
                ) / 1_000_000;

            return $dateIv;
        }

        /*
         * DateInterval represents negativity with invert=1.
         *
         * Convert the Duration into its canonical positive counterpart.
         */
        $positive = $this->negated();

        $dateIv = $positive->toDateInterval();
        $dateIv->invert = 1;

        return $dateIv;
    }

    public function toDate(): DatetimeImmutableUnit
    {
        return DatetimeImmutableUnit::now()
            ->add($this->toDateInterval());
    }

    public function __toString(): string
    {
        return (string)$this->toMillisecondsTime();
    }

    public function addMicroseconds(int $microseconds): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->microseconds + $microseconds;

        $milliseconds = intdiv($total, 1000);
        $remainder = $total % 1000;

        if ($remainder < 0) {
            --$milliseconds;
            $remainder += 1000;
        }

        $this->microseconds = $remainder;

        if ($milliseconds !== 0) {
            return $this->addMilliseconds($milliseconds);
        }

        return $this;
    }

    public function addMilliseconds(int $milliseconds): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->milliseconds + $milliseconds;

        $carry = intdiv($total, 1000);
        $remainder = $total % 1000;

        if ($remainder < 0) {
            --$carry;
            $remainder += 1000;
        }

        $this->milliseconds = $remainder;

        if ($carry !== 0) {
            return $this->addSeconds($carry);
        }

        return $this;
    }

    public function addSeconds(int $seconds): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->seconds + $seconds;

        $carry = intdiv($total, 60);
        $remainder = $total % 60;

        if ($remainder < 0) {
            --$carry;
            $remainder += 60;
        }

        $this->seconds = $remainder;

        if ($carry !== 0) {
            return $this->addMinutes($carry);
        }

        return $this;
    }

    public function addMinutes(int $minutes): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->minutes + $minutes;

        $carry = intdiv($total, 60);
        $remainder = $total % 60;

        if ($remainder < 0) {
            --$carry;
            $remainder += 60;
        }

        $this->minutes = $remainder;

        if ($carry !== 0) {
            return $this->addHours($carry);
        }

        return $this;
    }

    public function addHours(int $hours): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->hours + $hours;

        $carry = intdiv($total, 24);
        $remainder = $total % 24;

        if ($remainder < 0) {
            --$carry;
            $remainder += 24;
        }

        $this->hours = $remainder;

        if ($carry !== 0) {
            return $this->addDays($carry);
        }

        return $this;
    }

    public function addDays(int $days): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->days + $days;

        $carry = intdiv($total, 30);
        $remainder = $total % 30;

        if ($remainder < 0) {
            --$carry;
            $remainder += 30;
        }

        $this->days = $remainder;

        if ($carry !== 0) {
            return $this->addMonths($carry);
        }

        return $this;
    }

    public function addMonths(int $months): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $total = $this->months + $months;

        $carry = intdiv($total, 12);
        $remainder = $total % 12;

        if ($remainder < 0) {
            --$carry;
            $remainder += 12;
        }

        $this->months = $remainder;

        if ($carry !== 0) {
            return $this->addYears($carry);
        }

        return $this;
    }

    public function addYears(int $years): self
    {
        if ($this->isImmutable()) {
            return $this;
        }

        $this->years += $years;

        return $this;
    }

    /**
     * Return a new Duration with the opposite sign.
     */
    public function negated(): self
    {
        /*
         * We intentionally construct this through add*() so the result
         * gets canonicalized correctly.
         */
        return (new self())
            ->addMicroseconds(-$this->microseconds)
            ->addMilliseconds(-$this->milliseconds)
            ->addSeconds(-$this->seconds)
            ->addMinutes(-$this->minutes)
            ->addHours(-$this->hours)
            ->addDays(-$this->days)
            ->addMonths(-$this->months)
            ->addYears(-$this->years);
    }

    public function __set(string $name, mixed $value): void
    {
        if ($this->isImmutable()) {
            return;
        }

        if (!is_int($value)) {
            return;
        }

        /*
         * Preserve the current API semantics:
         *
         *   $duration->seconds = 10;
         *
         * means add 10 seconds.
         *
         * Negative values are intentionally rejected here because
         * callers wanting subtraction should use addSeconds(-10).
         */
        if ($value < 0) {
            return;
        }

        switch (strtolower($name)) {
            case 'microseconds':
                $this->addMicroseconds($value);
                break;

            case 'milliseconds':
                $this->addMilliseconds($value);
                break;

            case 'seconds':
                $this->addSeconds($value);
                break;

            case 'minutes':
                $this->addMinutes($value);
                break;

            case 'hours':
                $this->addHours($value);
                break;

            case 'days':
                $this->addDays($value);
                break;

            case 'months':
                $this->addMonths($value);
                break;

            case 'years':
                $this->addYears($value);
                break;
        }
    }

    public function __get(string $name): int|bool|null
    {
        return match (strtolower($name)) {
            'microseconds' => $this->microseconds,
            'milliseconds' => $this->milliseconds,
            'seconds' => $this->seconds,
            'minutes' => $this->minutes,
            'hours' => $this->hours,
            'days' => $this->days,
            'months' => $this->months,
            'years' => $this->years,
            'mutable' => !$this->isImmutable(),
            'immutable' => $this->isImmutable(),
            default => null,
        };
    }

    public function __isset(string $name): bool
    {
        return match (strtolower($name)) {
            'microseconds', 'years', 'milliseconds', 'seconds', 'minutes',
            'hours', 'days', 'months', 'mutable', 'immutable' => true,
            default => false,
        };
    }

    public function __unset(string $name): void
    {
        if ($this->isImmutable()) {
            return;
        }

        switch (strtolower($name)) {
            case 'years':
                $this->years = 0;
                break;

            case 'months':
                $this->months = 0;
                break;

            case 'days':
                $this->days = 0;
                break;

            case 'hours':
                $this->hours = 0;
                break;

            case 'minutes':
                $this->minutes = 0;
                break;

            case 'seconds':
                $this->seconds = 0;
                break;

            case 'milliseconds':
                $this->milliseconds = 0;
                break;

            case 'microseconds':
                $this->microseconds = 0;
                break;
        }
    }

    public function toArray(): array
    {
        return [
            'microseconds' => $this->microseconds,
            'milliseconds' => $this->milliseconds,
            'seconds' => $this->seconds,
            'minutes' => $this->minutes,
            'hours' => $this->hours,
            'days' => $this->days,
            'months' => $this->months,
            'years' => $this->years,
            'immutable' => $this->isImmutable(),
        ];
    }

    public function serialize(): ?string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $data): void
    {
        $this->__unserialize(unserialize($data));
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    public function __unserialize(array $data): void
    {
        /*
         * Do NOT use __set() here.
         *
         * __set() means "add", while unserialization needs "assign".
         */
        $this->microseconds = (int)($data['microseconds'] ?? 0);
        $this->milliseconds = (int)($data['milliseconds'] ?? 0);
        $this->seconds = (int)($data['seconds'] ?? 0);
        $this->minutes = (int)($data['minutes'] ?? 0);
        $this->hours = (int)($data['hours'] ?? 0);
        $this->days = (int)($data['days'] ?? 0);
        $this->months = (int)($data['months'] ?? 0);
        $this->years = (int)($data['years'] ?? 0);
        $this->immutable = (bool)($data['immutable'] ?? false);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return is_string($offset) && $this->__isset($offset);
    }

    public function offsetGet(mixed $offset): int|bool|null
    {
        if (is_string($offset)) {
            return $this->__get($offset);
        }

        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_string($offset)) {
            $this->__set($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (is_string($offset)) {
            $this->__unset($offset);
        }
    }
}
