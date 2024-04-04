<?php

namespace Flute\Modules\BansComms\Driver\Items;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableColumn;
use Flute\Core\Table\TablePreparation;
use Flute\Modules\BansComms\Contracts\DriverInterface;

class MaterialAdminDriver implements DriverInterface
{
    public function getSupportedMods(): array
    {
        return [];
    }

    public function getCommsColumns(): array
    {
        return [
            (new TableColumn('created', __('banscomms.table.end_date')))->setRender("{{CREATED}}", '
                function(data, type) {
                    if (type === "display") {
                        let date = new Date(data * 1000);
                        return ("0" + (date.getMonth() + 1)).slice(-2) + "-" +
                            ("0" + date.getDate()).slice(-2) + "-" +
                            date.getFullYear() + " " +
                            ("0" + date.getHours()).slice(-2) + ":" +
                            ("0" + date.getMinutes()).slice(-2);
                    }
                    return data;
                }
            '),
            (new TableColumn('type', __('banscomms.table.type')))->setRender("{{ICON_TYPE}}", '
                function(data, type) {
                    if (type === "display") {
                        return data == 1 ? `<i class="type-icon ph-bold ph-microphone-slash"></i>` : `<i class="type-icon ph-bold ph-chat-circle-dots"></i>`;
                    }
                    return data;
                }
            '),
            (new TableColumn('name', __('banscomms.table.loh'))),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
            (new TableColumn('admin_name', __('banscomms.table.admin')))->setType('text'),
            (new TableColumn('ends'))->setType('text')->setVisible(false),
            (new TableColumn('length'))->setType('text')->setVisible(false),
            (new TableColumn('RemoveType'))->setType('text')->setVisible(false),
            (new TableColumn('RemovedOn'))->setType('text')->setVisible(false),
            (new TableColumn('', 'Срок'))->setSearchable(false)->setOrderable(false)->setRender('{{KEY}}', "
                function(data, type, full) {
                    let length = full[6];
                    let removeType = full[7];
                    let ends = full[5];

                    if (length == '0' && removeType != 'U') {
                        return '<div class=\"ban-chip bans-forever\">'+ t(\"banscomms.table.forever\") +'</div>';
                    } else if (removeType == 'U') {
                        return '<div class=\"ban-chip bans-unban\">'+ t(\"banscomms.table.unbaned\") +'</div>';
                    } else if (length < '0' && Date.now() >= ends * 1000) {
                        return '<div class=\"ban-chip bans-session\">'+ t(\"banscomms.table.session\") +'</div>';
                    } else if (Date.now() >= ends * 1000 && length != '0') {
                        return '<div class=\"ban-chip bans-end\">' + secondsToReadable(length) + '</div>';
                    } else {
                        return '<div class=\"ban-chip\">' + secondsToReadable(length) + '</div>';
                    }
                }
            "),
        ];
    }

    public function getBansColumns(): array
    {
        return [
            (new TableColumn('created', __('banscomms.table.end_date')))->setRender("{{CREATED}}", '
                function(data, type) {
                    if (type === "display") {
                        let date = new Date(data * 1000);
                        return ("0" + (date.getMonth() + 1)).slice(-2) + "-" +
                            ("0" + date.getDate()).slice(-2) + "-" +
                            date.getFullYear() + " " +
                            ("0" + date.getHours()).slice(-2) + ":" +
                            ("0" + date.getMinutes()).slice(-2);
                    }
                    return data;
                }
            '),
            (new TableColumn('name', __('banscomms.table.loh'))),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
            (new TableColumn('admin_name', __('banscomms.table.admin')))->setType('text'),
            (new TableColumn('ends'))->setType('text')->setVisible(false),
            (new TableColumn('length'))->setType('text')->setVisible(false),
            (new TableColumn('RemoveType'))->setType('text')->setVisible(false),
            (new TableColumn('RemovedOn'))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))->setSearchable(false)->setOrderable(false)->setRender('{{KEY}}', "
                function(data, type, full) {
                    let length = full[5];
                    let removeType = full[6];
                    let ends = full[4];

                    if (length == '0' && removeType != 'U') {
                        return '<div class=\"ban-chip bans-forever\">'+ t(\"banscomms.table.forever\") +'</div>';
                    } else if (removeType == 'U') {
                        return '<div class=\"ban-chip bans-unban\">'+ t(\"banscomms.table.unbaned\") +'</div>';
                    } else if (length < '0' && Date.now() >= ends * 1000) {
                        return '<div class=\"ban-chip bans-session\">'+ t(\"banscomms.table.session\") +'</div>';
                    } else if (Date.now() >= ends * 1000 && length != '0') {
                        return '<div class=\"ban-chip bans-end\">' + secondsToReadable(length) + '</div>';
                    } else {
                        return '<div class=\"ban-chip\">' + secondsToReadable(length) + '</div>';
                    }
                }
            "),
        ];
    }

    public function getUserStats(int $sid, User $user): array
    {
        // in the future
        return [];
    }

    public function getComms(
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = []
    ): array {
        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms');

        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        $result = $select->fetchAll();

        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                ['created', 'type', 'name', 'reason', 'admin_name', 'ends', 'length', 'RemoveType', 'RemovedOn', ''],
                $result
            )
        ];
    }

    public function getBans(
        Server $server,
        string $dbname,
        int $page,
        int $perPage,
        int $draw,
        array $columns = [],
        array $search = [],
        array $order = []
    ): array {
        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order);

        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        $result = $select->fetchAll();

        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                ['created', 'name', 'reason', 'admin_name', 'ends', 'length', 'RemoveType', 'RemovedOn', ''],
                $result
            )
        ];
    }

    private function prepareSelectQuery(Server $server, string $dbname, array $columns, array $search, array $order, string $table = 'bans'): \Spiral\Database\Query\SelectQuery
    {
        $select = dbal()->database($dbname)->table($table)->select()->columns([
            "$table.*",
            'admins.user as admin_name'
        ]);

        foreach ($columns as $column) {
            if ($column['searchable'] == 'true' && $column['search']['value'] != '') {
                $select->where($column['name'], 'like', "%" . $column['search']['value'] . "%");
            }
        }

        if (isset($search['value']) && !empty($search['value'])) {
            $select->where('name', 'like', "%" . $search['value'] . "%")
                ->orWhere('reason', 'like', "%" . $search['value'] . "%")
                ->orWhere('authid', 'like', "%" . $search['value'] . "%");
        }

        foreach ($order as $order) {
            $columnIndex = $order['column'];
            $columnName = $columns[$columnIndex]['name'];
            $direction = $order['dir'] === 'asc' ? 'ASC' : 'DESC';

            if ($columns[$columnIndex]['orderable'] == 'true') {
                $select->orderBy($columnName, $direction);
            }
        }

        $select->innerJoin('admins')->on(["$table.aid" => 'admins.aid']);

        if ($server) {
            $select->innerJoin('servers')->on(["$table.sid" => 'servers.sid'])->where([
                'servers.ip' => $server->ip,
                'servers.port' => $server->port
            ]);
        }

        return $select;
    }

    public function getName(): string
    {
        return "MaterialAdmin";
    }
}