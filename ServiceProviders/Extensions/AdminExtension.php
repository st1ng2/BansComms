<?php

namespace Flute\Modules\BansComms\ServiceProviders\Extensions;

use Flute\Core\Admin\Builders\AdminSidebarBuilder;

class AdminExtension implements \Flute\Core\Contracts\ModuleExtensionInterface
{
    public function register(): void
    {
        $this->addSidebar();
    }

    private function addSidebar(): void
    {
        AdminSidebarBuilder::add('additional', [
            'title' => 'banscomms.admin.title',
            'icon' => 'ph-calendar-x',
            'permission' => 'admin.servers',
            'url' => '/admin/banscomms/list'
        ]);
    }
}