<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use JsonSerializable;
use Serializable;

interface PluginRatingInterface extends JsonSerializable, Serializable
{
    /**
     * Get the ratings for the plugin.
     *
     * @return array{
     *     5: int,
     *     4: int,
     *     3: int,
     *     2: int,
     *     1: int
     * } The array of ratings,where the keys are the rating values (1-5)
     * and the values are the number of ratings for each value.
     */
    public function getRatings(): array;

    /**
     * Get the average rating for the plugin.
     *
     * @return int The average rating, rounded to the nearest integer.
     */
    public function getAverageRating(): int;

    /**
     * Get the total count of ratings for the plugin.
     *
     * @return int The total count of ratings.
     */
    public function getRatingCount(): int;

    /**
     * Get the count of 1-star ratings for the plugin.
     *
     * @return int The count of 1-star ratings, or null if there are no ratings.
     */
    public function getOneStarCount(): int;

    /**
     * Get the count of 2-star ratings for the plugin.
     *
     * @return int The count of 2-star ratings, or null if there are no ratings.
     */
    public function getTwoStarCount(): int;

    /**
     * Get the count of 3-star ratings for the plugin.
     *
     * @return int The count of 3-star ratings, or null if there are no ratings.
     */
    public function getThreeStarCount(): int;

    /**
     * Get the count of 4-star ratings for the plugin.
     *
     * @return int The count of 4-star ratings, or null if there are no ratings.
     */
    public function getFourStarCount(): int;

    /**
     * Get the count of 5-star ratings for the plugin.
     *
     * @return int The count of 5-star ratings, or null if there are no ratings.
     */
    public function getFiveStarCount(): int;

    /**
     * @return array{
     *     5: int,
     *     4: int,
     *     3: int,
     *     2: int,
     *     1: int
     * }
     */
    public function jsonSerialize(): array;
}
