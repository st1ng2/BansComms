<?php

namespace Flute\Modules\BansComms\ServiceProviders\Extensions;

use Flute\Core\Contracts\ModuleExtensionInterface;
use Flute\Modules\BansComms\Driver\DriverFactory;
use Flute\Modules\BansComms\Services\BansCommsService;

class LoadDriversExtension implements ModuleExtensionInterface
{
    public function register() : void
    {
        app()->getContainer()->set(DriverFactory::class, new DriverFactory);

        app()->getContainer()->get(BansCommsService::class);
    }
}