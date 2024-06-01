<?php

namespace Flute\Modules\BansComms\ServiceProviders;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\BansComms\ServiceProviders\Extensions\LoadDriversExtension;
use Flute\Modules\BansComms\ServiceProviders\Extensions\LoadProfileExtension;
use Flute\Modules\BansComms\ServiceProviders\Extensions\LoadWidgetsExtension;
use Flute\Modules\BansComms\ServiceProviders\Extensions\RoutesExtension;

class BansCommsServiceProvider extends ModuleServiceProvider
{
    public array $extensions = [
        RoutesExtension::class,
        LoadDriversExtension::class,
        LoadProfileExtension::class,
        LoadWidgetsExtension::class
    ];

    public function boot(\DI\Container $container): void
    {
        $this->loadTranslations();
    }

    public function register(\DI\Container $container): void
    {
    }
}