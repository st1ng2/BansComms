<?php

namespace Flute\Modules\BansComms\ServiceProviders\Extensions;

use Flute\Modules\BansComms\Profile\BansCommsTab;

class LoadProfileExtension implements \Flute\Core\Contracts\ModuleExtensionInterface
{
    public function register(): void
    {
        $this->registerProfile();
    }

    private function registerProfile(): void
    {
        profile()->addTab(new BansCommsTab);
    }
}