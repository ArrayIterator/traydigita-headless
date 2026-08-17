<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Plugins;

use TrayDigita\WP\Headless\Resource\Interfaces\Plugins\PluginRatingInterface;
use function is_numeric;

class PluginRating implements PluginRatingInterface
{
    protected int $oneStarCount = 0;

    protected int $twoStarCount = 0;

    protected int $threeStarCount = 0;

    protected int $fourStarCount = 0;

    protected int $fiveStarCount = 0;

    public function getRatings(): array
    {
        return [
            1 => $this->getOneStarCount(),
            2 => $this->getTwoStarCount(),
            3 => $this->getThreeStarCount(),
            4 => $this->getFourStarCount(),
            5 => $this->getFiveStarCount(),
        ];
    }

    public function getAverageRating(): int
    {
        $ratings = $this->getRatings();
        $totalRatings = array_sum($ratings);
        if ($totalRatings === 0) {
            return 0;
        }
        $weightedSum = 0;
        foreach ($ratings as $star => $count) {
            $weightedSum += $star * $count;
        }
        return (int)round($weightedSum / $totalRatings);
    }

    public function getRatingCount(): int
    {
        return array_sum($this->getRatings());
    }

    public function getOneStarCount(): int
    {
        return $this->oneStarCount;
    }

    public function getTwoStarCount(): int
    {
        return $this->twoStarCount;
    }

    public function getThreeStarCount(): int
    {
        return $this->threeStarCount;
    }

    public function getFourStarCount(): int
    {
        return $this->fourStarCount;
    }

    public function getFiveStarCount(): int
    {
        return $this->fiveStarCount;
    }

    public function jsonSerialize(): array
    {
        return $this->getRatings();
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
        return $this->getRatings();
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $star => $count) {
            if (is_numeric($count)) {
                $count = (int)$count;
            }
            switch ($star) {
                case 1:
                    $this->oneStarCount = $count;
                    break;
                case 2:
                    $this->twoStarCount = $count;
                    break;
                case 3:
                    $this->threeStarCount = $count;
                    break;
                case 4:
                    $this->fourStarCount = $count;
                    break;
                case 5:
                    $this->fiveStarCount = $count;
                    break;
            }
        }
    }
}
