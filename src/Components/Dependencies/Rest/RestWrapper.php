<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\Rest;

use TrayDigita\WP\Headless\Resource\Components\Rest;
use WP_REST_Controller;

class RestWrapper extends WP_REST_Controller
{
    public function __construct(
        public readonly Rest $rest
    ) {
    }
}
