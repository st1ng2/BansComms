<?php

namespace Flute\Modules\BansComms\Services;

use Flute\Core\Database\Entities\DatabaseConnection;
use Flute\Core\Database\Entities\User;
use Flute\Modules\BansComms\Driver\DriverFactory;
use Flute\Modules\BansComms\Exceptions\ModNotFoundException;
use Flute\Modules\BansComms\Exceptions\ServerNotFoundException;

class BansCommsService
{
    protected array $serverModes = [];
    protected DriverFactory $driverFactory;
    protected const CACHE_KEY = 'flute.banscomms.servers';
    protected const CACHE_TIME = 3600;

    /**
     * BansCommsService constructor.
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
     * @param string $type Type of table, 'bans' or 'comms'.
     * @param User|null $user User instance.
     * @return mixed Table rendering result.
     * @throws \Exception If the module is not configured or server is not found.
     */
    public function generateTable(?int $sid = null, string $type = 'bans', ?User $user = null)
    {
        $this->validateServerModes();

        $server = $this->getServerFromModes($sid);

        $factory = $this->getDriverFactory($server);

        $url = $user ?
            ($type === 'bans' ? "banscomms/user/{$user->id}/{$server['server']->id}" : "banscomms/user/comms/{$user->id}/{$server['server']->id}") :
            ($type === 'bans' ? "banscomms/get/{$server['server']->id}" : "banscomms/get/comms/{$server['server']->id}");

        $table = table(url($url));

        $type === 'bans' ? $factory->getBansColumns($table) : $factory->getCommsColumns($table);

        return $table->render();
    }

    public function getUserBans(
        User $user,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = [],
        ?int $sid = null
    ) {
        $this->validateServerModes();

        try {
            $serverConfig = $this->getServerFromModes($sid);
            $factory = $this->getDriverFactory($serverConfig);

            return $factory->getUserBans(
                $user,
                $serverConfig['server'],
                $serverConfig['db'],
                $page,
                $perPage,
                $draw,
                $columns,
                $search,
                $order
            );
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getUserComms(
        User $user,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = [],
        ?int $sid = null
    ) {
        $this->validateServerModes();

        try {
            $serverConfig = $this->getServerFromModes($sid);
            $factory = $this->getDriverFactory($serverConfig);

            return $factory->getUserComms(
                $user,
                $serverConfig['server'],
                $serverConfig['db'],
                $page,
                $perPage,
                $draw,
                $columns,
                $search,
                $order
            );
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

        if (!$dbConnection) {
            throw new ModNotFoundException($sid);
        }

        return $dbConnection->mod;
    }

    /**
     * Get all server modes.
     * 
     * @return array
     */
    public function getServerModes(): array
    {
        return $this->serverModes;
    }

    /**
     * Get the total counts of bans, mutes, and gags across all servers, excluding specific admins.
     *
     * @param array $excludeAdmins Array of SteamIDs to exclude from counts.
     * @return array An array with the total counts of bans, mutes, gags, and unique admins.
     */
    public function getCountsForAllServers(array &$excludeAdmins = []): array
    {
        $this->validateServerModes();

        $totalCounts = [
            'bans' => 0,
            'mutes' => 0,
            'gags' => 0,
            'admins' => 0
        ];

        $wasAll = [];

        foreach ($this->serverModes as $serverMode) {
            $serverCounts = $this->getCounts($serverMode['server']->id, $excludeAdmins, isset($wasAll[$serverMode['factory']]));

            $totalCounts['bans'] += $serverCounts['bans'];
            $totalCounts['mutes'] += $serverCounts['mutes'];
            $totalCounts['gags'] += $serverCounts['gags'];
            $totalCounts['admins'] += $serverCounts['admins'];

            $wasAll[$serverMode['factory']] = true;
        }

        return $totalCounts;
    }

    /**
     * Get the total counts of bans, mutes, and gags, excluding specific admins.
     *
     * @param int|null $sid Server ID.
     * @param array $excludeAdmins Array of SteamIDs to exclude from counts.
     * @return array An array with the counts of bans, mutes, gags, and unique admins.
     * @throws \Exception If the module is not configured or server is not found.
     */
    public function getCounts(?int $sid = null, array &$excludeAdmins = [], bool $wasAll = false): array
    {
        $this->validateServerModes();

        $server = $this->getServerFromModes($sid);
        $factory = $this->getDriverFactory($server);

        return $factory->getCounts($server['db'], $excludeAdmins);
    }

    /**
     * Imports and registers drivers to the factory.
     */
    protected function importDrivers(): void
    {
        $driversNamespace = 'Flute\\Modules\\BansComms\\Driver\\Items\\';
        $driversPath = BASE_PATH . 'app/Modules/BansComms/Driver/Items';

        $finder = finder()->files()->in($driversPath)->name('*.php');

        foreach ($finder as $file) {
            $className = $driversNamespace . $file->getBasename('.php');

            if (class_exists($className)) {
                $driverInstance = new $className();
                if (method_exists($driverInstance, 'getName')) {
                    $this->driverFactory->registerDriver($driverInstance->getName(), $className);
                }
            }
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
        if (empty($this->serverModes)) {
            throw new \Exception(__('banscomms.module_is_not_configured'));
        }
    }

    /**
     * Get the server mode driver normal key
     * 
     * @return string|null
     */
    protected function getServerModeDriver(string $driver)
    {
        $drivers = $this->driverFactory->getAllDrivers();

        if (isset($drivers[$driver])) {
            return $driver;
        }

        if ($search = array_search($driver, $drivers)) {
            return $search;
        }

        return null;
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

        if (!isset($this->serverModes[$sid])) {
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
            $modes = $modes->orWhere('mod', $key)->orWhere('mod', $driver);
        }

        $modes = $modes->fetchAll();

        foreach ($modes as $mode) {
            if (!config("database.databases.{$mode->dbname}") || empty($mode->server)) {
                continue;
            }

            $this->serverModes[$mode->server->id] = [
                'server' => $mode->server,
                'db' => $mode->dbname,
                'factory' => $this->getServerModeDriver($mode->mod),
                'additional' => $mode->additional ? \Nette\Utils\Json::decode($mode->additional) : [],
            ];
        }
    }
}
