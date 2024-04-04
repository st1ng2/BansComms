<?php

namespace Flute\Modules\BansComms\Services;

use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\User;
use Flute\Modules\BansComms\Driver\DriverFactory;
use Flute\Modules\BansComms\Driver\Items\IKSDriver;
use Flute\Modules\BansComms\Driver\Items\MaterialAdminDriver;
use Flute\Modules\BansComms\Exceptions\ModNotFoundException;
use Flute\Modules\BansComms\Exceptions\ServerNotFoundException;

class BansCommsService
{
    protected array $serverModes = [];
    protected array $defaultDrivers = [
        MaterialAdminDriver::class,
        IKSDriver::class
    ];

    protected DriverFactory $driverFactory;
    protected const CACHE_KEY = 'flute.banscomms.servers';
    protected const CACHE_TIME = 3600;

    /**
     *
     * @param DriverFactory $driverFactory Factory for creating driver instances.
     */
    public function __construct(DriverFactory $driverFactory)
    {
        $this->driverFactory = $driverFactory;

        $this->importDrivers();
        $this->importServers();
    }

    /**
     * Generates a table for the given server ID.
     *
     * @param int|null $sid Server ID.
     * @return mixed Table rendering result.
     * @throws \Exception If the module is not configured or server is not found.
     */
    public function generateTable(?int $sid = null, string $type = 'bans')
    {
        $this->validateServerModes();

        $server = $this->getServerFromModes($sid);

        $factory = $this->getDriverFactory($server);

        $table = table(url($type === 'bans' ? "banscomms/get/{$server['server']->id}" : "banscomms/get/comms/{$server['server']->id}"));
        $table->addColumns($type === 'bans' ? $factory->getBansColumns() : $factory->getCommsColumns());

        return $table->render();
    }

    public function getUserBansComms(User $user, ?int $sid = null)
    {
        $this->validateServerModes();

        try {
            $serverConfig = $this->getServerFromModes($sid);

            $factory = $this->getDriverFactory($serverConfig);

            return $factory->getUserStats($serverConfig['server']->id, $user);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retrieves the mode of a server by its ID.
     *
     * @param int $sid Server ID.
     * @return mixed Mode of the server.
     * @throws ModNotFoundException If the mode is not found.
     */
    public function getMode(int $sid)
    {
        $dbConnection = rep(DatabaseConnection::class)->findOne(['server_id' => $sid]);

        if (!$dbConnection)
            throw new ModNotFoundException($sid);

        return $dbConnection->mod;
    }

    /**
     * Get all server modes
     * 
     * @return array
     */
    public function getServerModes(): array
    {
        return $this->serverModes;
    }

    /**
     * Imports and registers drivers to the factory.
     */
    protected function importDrivers(): void
    {
        foreach ($this->defaultDrivers as $driver) {
            $this->driverFactory->registerDriver($driver, $driver);
        }
    }

    /**
     * Imports server modes from the database and caches them if needed.
     */
    protected function importServers(): void
    {
        if (is_performance() && cache()->has(self::CACHE_KEY)) {
            $this->serverModes = cache()->get(self::CACHE_KEY);
            return;
        }

        $this->populateServerModes();

        if (is_performance()) {
            cache()->set(self::CACHE_KEY, $this->serverModes, self::CACHE_TIME);
        }
    }

    /**
     * Validates if server modes are configured.
     *
     * @throws \Exception If server modes are empty.
     */
    public function validateServerModes(): void
    {
        if (empty ($this->serverModes)) {
            throw new \Exception(__('banscomms.module_is_not_configured'));
        }
    }

    /**
     * Gets server configuration from server modes based on server ID.
     *
     * @param int|null $sid Server ID.
     * @return array Server configuration.
     * @throws ServerNotFoundException If the server is not found in the server modes.
     */
    public function getServerFromModes(?int $sid): array
    {
        if (!$sid) {
            $key = array_key_first($this->serverModes);

            $this->serverModes[$key]['current'] = true;

            return $this->serverModes[$key];
        }

        if (!isset ($this->serverModes[$sid])) {
            throw new ServerNotFoundException($sid);
        }

        $this->serverModes[$sid]['current'] = true;

        return $this->serverModes[$sid];
    }

    /**
     * Creates a driver instance using the DriverFactory.
     *
     * @param array $server Server configuration.
     * @return mixed Instance of the driver.
     * @throws \Exception If unable to create driver.
     */
    public function getDriverFactory(array $server)
    {
        try {
            return $this->driverFactory->createDriver($server['factory'], (array) $server['additional']);
        } catch (\RuntimeException $e) {
            logs()->error($e);
            throw new \Exception(__('def.unknown_error'));
        }
    }

    /**
     * Populates server modes from the database.
     */
    private function populateServerModes(): void
    {
        $modes = rep(DatabaseConnection::class)->select()->load('server');
        $drivers = $this->driverFactory->getAllDrivers();

        foreach ($drivers as $key => $driver) {
            $modes = $modes->orWhere('mod', $key);
        }

        $modes = $modes->fetchAll();

        foreach ($modes as $mode) {
            if (!config("database.databases.{$mode->dbname}") || empty ($mode->server)) {
                continue;
            }

            $this->serverModes[$mode->server->id] = [
                'server' => $mode->server,
                'db' => $mode->dbname,
                'factory' => $mode->mod,
                'additional' => $mode->additional ? \Nette\Utils\Json::decode($mode->additional) : [],
            ];
        }
    }
}