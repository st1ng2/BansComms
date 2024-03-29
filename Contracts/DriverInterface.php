<?php

namespace Flute\Modules\BansComms\Contracts;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableColumn;

interface DriverInterface
{
    /**
     * @var array[TableColumn]
     */
    public function getCommsColumns(): array;

    /**
     * @var array[TableColumn]
     */
    public function getBansColumns(): array;

    public function getBans(
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columuns = [],
        array $search = [],
        array $order = []
    ): array;

    public function getComms(
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columuns = [],
        array $search = [],
        array $order = []
    ): array;

    /**
     * Return driver name
     */
    public function getName(): string;
    public function getUserStats( int $sid, User $user ) : array;
}