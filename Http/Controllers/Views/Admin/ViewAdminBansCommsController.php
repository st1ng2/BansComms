<?php

namespace Flute\Modules\BansComms\Http\Controllers\Views\Admin;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\Server;
use Flute\Core\Support\AbstractController;
use Flute\Core\Table\TableColumn;
use Flute\Modules\BansComms\Driver\DriverFactory;
use Flute\Modules\BansComms\Services\AdminBansCommsService;
use Spiral\Database\Injection\Parameter;
use Symfony\Component\HttpFoundation\Response;

class ViewAdminBansCommsController extends AbstractController
{
    protected $driverFactory;
    protected $service;

    public function __construct(DriverFactory $driverFactory, AdminBansCommsService $service)
    {
        $this->driverFactory = $driverFactory;
        $this->service = $service;
        HasPermissionMiddleware::permission(['admin', 'admin.servers']);
    }

    public function list(): Response
    {
        $table = table();

        $result = rep(DatabaseConnection::class)->select();

        foreach ($this->driverFactory->getAllDrivers() as $key => $driver) {
            $result->orWhere('mod', $key);
        }

        $result = $result->fetchAll();

        foreach ($result as $key => $row) {
            $result[$key]->mod = basename($row->mod);
            $result[$key]->server = ($result[$key]->server->id . ' - ' . $result[$key]->server->name);
        }

        $table->addColumns([
            (new TableColumn('id'))->setVisible(false),
            (new TableColumn('mod', 'Driver')),
            (new TableColumn('dbname', __('banscomms.admin.dbname'))),
            (new TableColumn('server', __('banscomms.admin.server'))),
        ])->withActions('banscomms');

        $table->setData($result);

        return view('Modules/BansComms/Resources/views/admin/list', [
            'table' => $table->render()
        ]);
    }

    public function update($id): Response
    {
        try {
            $connection = $this->service->find($id);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 404);
        }

        $drivers = $this->getDrivers();

        return view('Modules/BansComms/Resources/views/admin/edit', [
            'connection' => $connection,
            'drivers' => $drivers,
            'servers' => $this->getServers()
        ]);
    }

    public function add(): Response
    {
        $drivers = $this->getDrivers();

        return view('Modules/BansComms/Resources/views/admin/add', [
            'drivers' => $drivers,
            'servers' => $this->getServers()
        ]);
    }

    protected function getDrivers(): array
    {
        return $this->driverFactory->getAllDrivers();
    }

    protected function getServers(): array
    {
        $servers = rep(Server::class)->select();
        $drivers = rep(DatabaseConnection::class)->select();

        foreach ($this->getDrivers() as $key => $driver) {
            $drivers->where('mod', $key);
        }

        $drivers = $drivers->fetchAll();

        foreach ($drivers as $key => $driver) {
            if ($getDriver = $this->driverFactory->createDriver($driver->mod)) {

                if (!empty($getDriver->getSupportedMods())) {
                    $servers->where('mod', 'IN', new Parameter($getDriver->getSupportedMods()));
                }

            } else {
                unset($drivers[$key]);
            }

            $servers->where('id', '!=', $driver->server->id);
        }

        return $servers->fetchAll();
    }
}