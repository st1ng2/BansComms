<?php

namespace Flute\Modules\BansComms\ServiceProviders\Extensions;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Router\RouteGroup;
use Flute\Modules\BansComms\Http\Controllers\API\ApiBansController;
use Flute\Modules\BansComms\Http\Controllers\API\Admin\AdminBansCommsController;
use Flute\Modules\BansComms\Http\Controllers\API\ApiCommsController;
use Flute\Modules\BansComms\Http\Controllers\Views\Admin\ViewAdminBansCommsController;
use Flute\Modules\BansComms\Http\Controllers\Views\ViewBansController;
use Flute\Modules\BansComms\Http\Controllers\Views\ViewCommsController;

class RoutesExtension implements \Flute\Core\Contracts\ModuleExtensionInterface
{
    public function register(): void
    {
        router()->group(function (RouteGroup $routeGroup) {
            $routeGroup->get('/', [ViewBansController::class, 'index']);

            $routeGroup->get('/comms', [ViewCommsController::class, 'index']);

            $routeGroup->get('/get/{sid}', [ApiBansController::class, 'getData']);
            $routeGroup->get('/get/comms/{sid}', [ApiCommsController::class, 'getData']);
        }, 'banscomms');

        router()->group(function (RouteGroup $routeGroup) {
            $routeGroup->middleware(HasPermissionMiddleware::class);

            $routeGroup->group(function (RouteGroup $news) {
                $news->get('list', [ViewAdminBansCommsController::class, 'list']);
                $news->get('add', [ViewAdminBansCommsController::class, 'add']);
                $news->get('edit/{id}', [ViewAdminBansCommsController::class, 'update']);
            }, 'banscomms/');
        
            $routeGroup->group(function (RouteGroup $news) {
                $news->post('add', [AdminBansCommsController::class, 'store']);
                $news->put('{id}', [AdminBansCommsController::class, 'update']);
                $news->delete('{id}', [AdminBansCommsController::class, 'delete']);
            }, 'api/banscomms/');
        }, 'admin/');
    }
}