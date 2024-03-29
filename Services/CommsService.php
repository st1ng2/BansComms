<?php

namespace Flute\Modules\BansComms\Services;

class CommsService
{
    protected BansCommsService $service;

    public function __construct( BansCommsService $bansCommsService )
    {
        $this->service = $bansCommsService;
    }

    public function generateTable( ?int $sid = null )
    {
        return $this->service->generateTable($sid, 'comms');
    }
    
    public function getServerModes()
    {
        return $this->service->getServerModes();
    }

    /**
     * Fetches the data for a specific server based on various parameters.
     *
     * @param int $page Page number.
     * @param int $perPage Number of items per page.
     * @param int $draw Draw counter.
     * @param array $columns Column configuration.
     * @param array $search Search configuration.
     * @param array $order Order configuration.
     * @param int|null $sid Server ID.
     * @return array Data from the driver.
     * @throws \Exception If the module is not configured or server is not found.
     */
    public function getData(
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = [],
        ?int $sid = null
    ) {
        $this->service->validateServerModes();

        $server = $this->service->getServerFromModes($sid);

        $factory = $this->service->getDriverFactory($server);

        return $factory->getComms(
            $server['server'],
            $server['db'],
            $page,
            $perPage,
            $draw,
            $columns,
            $search,
            $order
        );
    }
}