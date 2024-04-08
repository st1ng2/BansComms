<?php

namespace Flute\Modules\BansComms;

use Flute\Core\Database\Entities\NavbarItem;

class Installer extends \Flute\Core\Support\AbstractModuleInstaller
{
    public function getNavItem(): ?NavbarItem
    {
        $navItem = new NavbarItem;
        $navItem->icon = 'ph ph-lock';
        $navItem->title = 'Bans & Comms';
        $navItem->url = '/banscomms/';
        
        return $navItem;
    }

    public function install(\Flute\Core\Modules\ModuleInformation &$module) : bool
    {
        return true;
    }

    public function uninstall(\Flute\Core\Modules\ModuleInformation &$module) : bool
    {
        return true;
    }
}