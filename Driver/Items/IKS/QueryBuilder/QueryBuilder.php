<?php

namespace Flute\Modules\BansComms\Driver\Items\IKS\QueryBuilder;

use Flute\Core\Database\Entities\Server;
use Spiral\Database\Injection\Fragment;

class QueryBuilder
{
    private $sid;

    public function __construct(int $sid)
    {
        $this->sid = $sid;
    }

    public function prepareSelectQuery(Server $server, string $dbname, array $columns, array $search, array $order, string $table = 'bans')
    {
        if ($table === 'comms') {
            $selectQueries = [];

            foreach (['mutes', 'gags'] as $tableName) {
                $select = $this->buildSelectQuery($dbname, $tableName, $columns, $search, $order);
                array_push($selectQueries, $select);
            }

            return $selectQueries;
        } else {
            return $this->buildSelectQuery($dbname, "bans", $columns, $search, $order, true);
        }
    }

    private function buildSelectQuery(string $dbname, string $tableName, array $columns, array $search, array $order, bool $isBans = false)
    {
        $select = dbal()->database($dbname)->table($tableName)->select()->columns([
            "$tableName.*",
            'admins.name as admin_name',
            new Fragment("'$tableName' as source")
        ]);

        foreach ($columns as $column) {
            if ($column['searchable'] === 'true' && !empty($column['search']['value'])) {
                $select->where($column['name'], 'like', '%' . $column['search']['value'] . '%');
            }
        }

        if (isset($search['value']) && !empty($search['value'])) {
            $select->where(function ($select) use ($search, $tableName) {
                $select->where("$tableName.name", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.sid", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.adminsid", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.reason", 'like', '%' . $search['value'] . '%');
            });
        }

        foreach ($order as $orderItem) {
            $columnIndex = $orderItem['column'];
            $columnName = $columns[$columnIndex]['name'];
            $direction = strtolower($orderItem['dir']) === 'asc' ? 'ASC' : 'DESC';

            if ($columns[$columnIndex]['orderable'] === 'true') {
                $select->orderBy("$tableName.$columnName", $direction);
            }
        }

        $select->innerJoin('admins')->on(["$tableName.adminsid" => 'admins.sid']);

        $select->where(function ($select) use ($tableName) {
            $select->where("$tableName.server_id", $this->sid)->orWhere("$tableName.server_id", '');
        });

        return $select;
    }
}