<?php

namespace Flute\Modules\BansComms\ServiceProviders\Extensions;

use Flute\Core\Contracts\ModuleExtensionInterface;
use Flute\Modules\BansComms\Widgets\MainBansStatsWidget;

class LoadWidgetsExtension implements ModuleExtensionInterface
{
    public function register() : void
    {
        widgets()->register(new MainBansStatsWidget());
    }
}