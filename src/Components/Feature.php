<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components;

use TrayDigita\WP\Headless\Resource\Features\Image;

class Feature
{
    public function __construct(public readonly Container $container)
    {
    }

    public function image(mixed $post) : Image
    {
        return new Image($this->container->postUtil, $post);
    }
}
