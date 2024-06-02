<?php

namespace Flute\Modules\BansComms\Contracts;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableBuilder;
use Flute\Core\Table\TableColumn;

/**
 * Interface DriverInterface
 *
 * Interface for implementing communication and ban management drivers.
 */
interface DriverInterface
{
    /**
     * Get the table columns for communications.
     */
    public function getCommsColumns(TableBuilder $tableBuilder);

    /**
     * Get the table columns for bans.
     */
    public function getBansColumns(TableBuilder $tableBuilder);

    /**
     * Retrieve a paginated list of bans.
     *
     * @param Server $server Server entity.
     * @param string $dbname Database name.
     * @param int $page Current page number.
     * @param int $perPage Number of records per page.
     * @param int $draw Datatable draw count.
     * @param array $columns Optional array of columns for sorting/filtering.
     * @param array $search Optional search criteria.
     * @param array $order Optional ordering criteria.
     * @return array Array of bans.
     */
    public function getBans(
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = []
    ): array;

    /**
     * Retrieve a paginated list of communications.
     *
     * @param Server $server Server entity.
     * @param string $dbname Database name.
     * @param int $page Current page number.
     * @param int $perPage Number of records per page.
     * @param int $draw Datatable draw count.
     * @param array $columns Optional array of columns for sorting/filtering.
     * @param array $search Optional search criteria.
     * @param array $order Optional ordering criteria.
     * @return array Array of communications.
     */
    public function getComms(
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = []
    ): array;

    /**
     * Return the name of the driver.
     *
     * @return string The driver name.
     */
    public function getName(): string;

    /**
     * Retrieve a paginated list of bans for a specific user.
     *
     * @param User $user User entity.
     * @param Server $server Server entity.
     * @param string $dbname Database name.
     * @param int $page Current page number.
     * @param int $perPage Number of records per page.
     * @param int $draw Datatable draw count.
     * @param array $columns Optional array of columns for sorting/filtering.
     * @param array $search Optional search criteria.
     * @param array $order Optional ordering criteria.
     * @return array Array of user-specific bans.
     */
    public function getUserBans(
        User $user,
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = []
    ): array;

    /**
     * Retrieve a paginated list of communications for a specific user.
     *
     * @param User $user User entity.
     * @param Server $server Server entity.
     * @param string $dbname Database name.
     * @param int $page Current page number.
     * @param int $perPage Number of records per page.
     * @param int $draw Datatable draw count.
     * @param array $columns Optional array of columns for sorting/filtering.
     * @param array $search Optional search criteria.
     * @param array $order Optional ordering criteria.
     * @return array Array of user-specific communications.
     */
    public function getUserComms(
        User $user,
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = []
    ): array;

    /**
     * Get the total counts of bans, mutes, and gags.
     *
     * @param string $dbname Database name.
     * 
     * @return array An array with the counts of bans, mutes, and gags.
     */
    public function getCounts(string $dbname, array &$excludeAdmins = [], bool $wasAll = false): array;
}
