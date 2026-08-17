<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Interfaces\Plugins;

use JsonSerializable;
use Serializable;

interface PluginScreenShotInterface extends JsonSerializable, Serializable
{
    /**
     * Get the screenshot source
     * @return string
     */
    public function getSrc() : string;

    /**
     * Get the screenshot caption
     * @return string
     */
    public function getCaption() : string;

    /**
     * @return array{
     *     src: string,
     *     caption: string
     * }
     */
    public function jsonSerialize(): array;
}
