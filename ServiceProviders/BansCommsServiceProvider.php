<?php

namespace Flute\Modules\BansComms\ServiceProviders;

use Flute\Core\Support\ModuleServiceProvider;
use Flute\Modules\BansComms\ServiceProviders\Extensions\AdminExtension;
use Flute\Modules\BansComms\ServiceProviders\Extensions\LoadDriversExtension;
use Flute\Modules\BansComms\ServiceProviders\Extensions\LoadProfileExtension;
use Flute\Modules\BansComms\ServiceProviders\Extensions\RoutesExtension;

class BansCommsServiceProvider extends ModuleServiceProvider
{
    public array $extensions = [
        RoutesExtension::class,
        LoadDriversExtension::class,
        AdminExtension::class,
        LoadProfileExtension::class
    ];

    public function boot(\DI\Container $container): void
    {
        // $this->loadEntities();
        $this->loadTranslations();
    }

    public function register(\DI\Container $container): void
    {
    }
}