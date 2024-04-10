<?php

namespace Flute\Modules\BansComms\Driver\Items;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableColumn;
use Flute\Core\Table\TablePreparation;
use Flute\Modules\BansComms\Contracts\DriverInterface;
use Spiral\Database\Injection\Fragment;

class SimpleAdminDriver implements DriverInterface
{
    public function getCommsColumns(): array
    {
        return [
            (new TableColumn('created', __('banscomms.table.created')))
                ->setRender("{{CREATED}}", $this->dateFormatRender()),
            (new TableColumn('type', __('banscomms.table.type')))
                ->setRender("{{ICON_TYPE}}", $this->typeFormatRender()),
            new TableColumn('player_name', __('banscomms.table.loh')),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
            (new TableColumn('admin_name', __('banscomms.table.admin')))->setType('text')->setOrderable(false),
            (new TableColumn('ends', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->dateFormatRender()),
            (new TableColumn('duration', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->timeFormatRender()),
        ];
    }

    public function getBansColumns(): array
    {
        return [
            (new TableColumn('created', __('banscomms.table.created')))
                ->setRender("{{CREATED}}", $this->dateFormatRender()),
            new TableColumn('player_name', __('banscomms.table.loh')),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
            (new TableColumn('admin_name', __('banscomms.table.admin')))->setType('text')->setOrderable(false),
            (new TableColumn('ends', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->dateFormatRender()),
            (new TableColumn('duration', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->timeFormatRenderBans()),
        ];
    }

    private function dateFormatRender(): string
    {
        return '
            function(data, type) {
                if (type === "display") {
                    let date = new Date(data);
                    return ("0" + (date.getMonth() + 1)).slice(-2) + "-" +
                           ("0" + date.getDate()).slice(-2) + "-" +
                           date.getFullYear() + " " +
                           ("0" + date.getHours()).slice(-2) + ":" +
                           ("0" + date.getMinutes()).slice(-2);
                }
                return data;
            }
        ';
    }

    private function typeFormatRender(): string
    {
        return '
            function(data, type) {
                if (type === "display") {
                    return data == "MUTE" ? `<i class="type-icon ph-bold ph-microphone-slash"></i>` : `<i class="type-icon ph-bold ph-chat-circle-dots"></i>`;
                }
                return data;
            }
        ';
    }

    private function timeFormatRender(): string
    {
        return "
            function(data, type, full) {
                let time = full[6];
                let ends = full[5];

                if (time == '0') {
                    return '<div class=\"ban-chip bans-forever\">'+ t(\"banscomms.table.forever\") +'</div>';
                } else if (Date.now() >= new Date(ends) && time != '0') {
                    return '<div class=\"ban-chip bans-end\">' + secondsToReadable(time) + '</div>';
                } else {
                    return '<div class=\"ban-chip\">' + secondsToReadable(time) + '</div>';
                }
            }
        ";
    }

    private function timeFormatRenderBans(): string
    {
        return "
            function(data, type, full) {
                let time = full[5];
                let ends = full[4];

                if (time == '0') {
                    return '<div class=\"ban-chip bans-forever\">'+ t(\"banscomms.table.forever\") +'</div>';
                } else if (Date.now() >= new Date(ends) && time != '0') {
                    return '<div class=\"ban-chip bans-end\">' + secondsToReadable(time) + '</div>';
                } else {
                    return '<div class=\"ban-chip\">' + secondsToReadable(time) + '</div>';
                }
            }
        ";
    }

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
    ): array {
        $steam = $user->getSocialNetwork('Steam') ?? $user->getSocialNetwork('HttpsSteam');
        
        if( !$steam ) return [];

        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'bans')
            ->where('bans.player_steamid', (int) $steam->value);

        // Применение пагинации
        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        // Получение данных
        $result = $select->fetchAll();

        // Формирование ответа
        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                ['created', 'player_name', 'reason', 'admin_name', 'ends', 'duration', ''],
                $result
            )
        ];
    }

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
    ): array {
        $steam = $user->getSocialNetwork('Steam') ?? $user->getSocialNetwork('HttpsSteam');

        if( !$steam ) return [];

        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'mutes');
        $select->where('mutes.player_steamid', (int) $steam->value);

        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        $result = $select->fetchAll();

        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                ['created', 'type', 'player_name', 'reason', 'admin_name', 'ends', 'duration', ''],
                $result
            )
        ];
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
        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'mutes');

        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        $result = $select->fetchAll();

        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                ['created', 'type', 'player_name', 'reason', 'admin_name', 'ends', 'duration', ''],
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
                ['created', 'player_name', 'reason', 'admin_name', 'ends', 'duration', ''],
                $result
            )
        ];
    }

    private function prepareSelectQuery(Server $server, string $dbname, array $columns, array $search, array $order, string $tableName = 'bans')
    {
        $select = dbal()->database($dbname)->table($tableName)->select()->columns([
            "$tableName.*",
            'admins.player_name as admin_name',
            new Fragment("'$tableName' as source")
        ]);

        // Applying column-based search
        foreach ($columns as $column) {
            if ($column['searchable'] === 'true' && !empty($column['search']['value'])) {
                $select->where($column['name'], 'like', '%' . $column['search']['value'] . '%');
            }
        }

        // Applying global search
        if (isset($search['value']) && !empty($search['value'])) {
            $select->where(function ($select) use ($search, $tableName) {
                $select->where("$tableName.player_steamid", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.reason", 'like', '%' . $search['value'] . '%');
            });
        }

        // Applying ordering
        foreach ($order as $orderItem) {
            $columnIndex = $orderItem['column'];
            $columnName = $columns[$columnIndex]['name'];
            $direction = strtolower($orderItem['dir']) === 'asc' ? 'ASC' : 'DESC';

            if ($columns[$columnIndex]['orderable'] === 'true') {
                $select->orderBy("$tableName.$columnName", $direction);
            }
        }

        // Join with admins table
        $select->innerJoin('admins')->on(["$tableName.admin_steamid" => 'admins.player_steamid']);

        if ($server) {
            $select->innerJoin('servers')->on(["$tableName.server_id" => 'servers.id'])->where([
                'servers.address' => $server->ip . ':' . $server->port,
            ]);
        }

        return $select;
    }

    public function getName(): string
    {
        return "IKS Admin";
    }
}