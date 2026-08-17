<?php
declare(strict_types=1);

namespace TrayDigita\WP\Headless\Resource\Components\Dependencies\AdminMenu;

use TrayDigita\WP\Headless\Resource\Abstracts\AbstractAdminPage;
use function __;

final class MainMenu extends AbstractAdminPage
{
    public function getPageTitle(): string
    {
        return $this->adminMenu->getPageTitle();
    }

    public function getMenuTitle(): string
    {
        return __('TrayDigita Headless', 'traydigita');
    }

    public function getSlug(): string
    {
        return $this->adminMenu->getSlug();
    }

    protected function dispatch()
    {
    }

    public function getTsxURL(): string
    {
        return '';
    }
}
