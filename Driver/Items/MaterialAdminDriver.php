<?php

namespace Flute\Modules\BansComms\Driver\Items;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableBuilder;
use Flute\Core\Table\TableColumn;
use Flute\Core\Table\TablePreparation;
use Flute\Modules\BansComms\Contracts\DriverInterface;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\Parameter;

class MaterialAdminDriver implements DriverInterface
{
    public function getSupportedMods(): array
    {
        return [];
    }

    public function getCommsColumns(TableBuilder $tableBuilder)
    {
        $tableBuilder->addColumn((new TableColumn('type', __('banscomms.table.type')))
            ->setRender("{{ICON_TYPE}}", $this->iconType()));

        $tableBuilder->addColumn((new TableColumn('user_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('avatar', 'name', __('banscomms.table.loh'), 'user_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('created', __('banscomms.table.created')))->setDefaultOrder()
                ->setRender("{{CREATED}}", $this->timeFormatter()),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
        ]);

        $tableBuilder->addColumn((new TableColumn('admin_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('admin_avatar', 'admin_name', __('banscomms.table.admin'), 'admin_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('ends', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->timeFormatter()),
            (new TableColumn('length', ''))->setType('text')->setVisible(false),
            (new TableColumn('RemoveType', ''))->setType('text')->setVisible(false),
            (new TableColumn('RemovedOn', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->lengthFormatter()),
        ]);
    }

    public function getBansColumns(TableBuilder $tableBuilder)
    {
        $tableBuilder->addColumn((new TableColumn('user_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('avatar', 'name', __('banscomms.table.loh'), 'user_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('created', __('banscomms.table.created')))->setDefaultOrder()
                ->setRender("{{CREATED}}", $this->timeFormatter()),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
        ]);

        $tableBuilder->addColumn((new TableColumn('admin_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('admin_avatar', 'admin_name', __('banscomms.table.admin'), 'admin_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('ends', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->timeFormatter()),
            (new TableColumn('length', ''))->setType('text')->setVisible(false),
            (new TableColumn('RemoveType', ''))->setType('text')->setVisible(false),
            (new TableColumn('RemovedOn', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->lengthFormatter(true)),
        ]);
    }

    protected function lengthFormatter($isBans = false)
    {
        return "
            function(data, type, full) {

                ".($isBans ? "
                let length = full[11];
                let ends = full[10];
                let removeType = full[12];
                " : "
                let length = full[12];
                let ends = full[11];
                let removeType = full[13];
                ")."
                

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
        ";
    }

    protected function iconType(): string
    {
        return '
            function(data, type) {
                if (type === "display") {
                    return data == 1 ? `<i class="type-icon ph-bold ph-microphone-slash"></i>` : `<i class="type-icon ph-bold ph-chat-circle-dots"></i>`;
                }
                return data;
            }
        ';
    }

    protected function timeFormatter(): string
    {
        return '
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
        ';
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

        $steam = steam()->steamid($steam->value)->RenderSteam2();

        if (!$steam)
            return [];

        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'bans')
            ->where('bans.authid', 'like', '%' . substr($steam, 10) . '%');

        // Применение пагинации
        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        // Получение данных
        $result = $select->fetchAll();

        $steamIds = $this->getSteamIds64($result);
        $usersData = steam()->getUsers($steamIds);

        $result = $this->mapUsersDataToResult($result, $usersData);

        // Формирование ответа
        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                [
                    'user_url',
                    'avatar',
                    'name',
                    '',
                    'created',
                    'reason',
                    'admin_url',
                    'admin_avatar',
                    'admin_name',
                    '',
                    'ends',
                    'length',
                    'RemoveType',
                    'RemovedOn',
                    ''
                ],
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

        $steam = steam()->steamid($steam->value)->RenderSteam2();

        if (!$steam)
            return [];

        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms')
            ->where('comms.authid', 'like', '%' . substr($steam, 10) . '%');

        // Применение пагинации
        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        // Получение данных
        $result = $select->fetchAll();

        $steamIds = $this->getSteamIds64($result);
        $usersData = steam()->getUsers($steamIds);

        $result = $this->mapUsersDataToResult($result, $usersData);

        // Формирование ответа
        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                [
                    'type',
                    'user_url',
                    'avatar',
                    'name',
                    '',
                    'created',
                    'reason',
                    'admin_url',
                    'admin_avatar',
                    'admin_name',
                    '',
                    'ends',
                    'length',
                    'RemoveType',
                    'RemovedOn',
                    ''
                ],
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
        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms');

        $paginator = new \Spiral\Pagination\Paginator($perPage);
        $paginate = $paginator->withPage($page)->paginate($select);

        $result = $select->fetchAll();

        $steamIds = $this->getSteamIds64($result);
        $usersData = steam()->getUsers($steamIds);

        $result = $this->mapUsersDataToResult($result, $usersData);

        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                [
                    'type',
                    'user_url',
                    'avatar',
                    'name',
                    '',
                    'created',
                    'reason',
                    'admin_url',
                    'admin_avatar',
                    'admin_name',
                    '',
                    'ends',
                    'length',
                    'RemoveType',
                    'RemovedOn',
                    ''
                ],
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

        $steamIds = $this->getSteamIds64($result);
        $usersData = steam()->getUsers($steamIds);

        $result = $this->mapUsersDataToResult($result, $usersData);

        return [
            'draw' => $draw,
            'recordsTotal' => $paginate->count(),
            'recordsFiltered' => $paginate->count(),
            'data' => TablePreparation::normalize(
                [
                    'user_url',
                    'avatar',
                    'name',
                    '',
                    'created',
                    'reason',
                    'admin_url',
                    'admin_avatar',
                    'admin_name',
                    '',
                    'ends',
                    'length',
                    'RemoveType',
                    'RemovedOn',
                    ''
                ],
                $result
            )
        ];
    }

    private function prepareSelectQuery(Server $server, string $dbname, array $columns, array $search, array $order, string $table = 'bans'): \Spiral\Database\Query\SelectQuery
    {
        $select = dbal()->database($dbname)->table($table)->select()->columns([
            "$table.*",
            'admins.user as admin_name',
            'admins.authid as admin_steam',
        ]);

        foreach ($columns as $column) {
            if ($column['searchable'] == 'true' && $column['search']['value'] != '') {
                $select->where($column['name'], 'like', "%" . $column['search']['value'] . "%");
            }
        }

        if (isset($search['value']) && !empty($search['value'])) {
            $select->where(function ($q) use ($search, $table) {
                $q->where('name', 'like', "%" . $search['value'] . "%")
                    ->orWhere('reason', 'like', "%" . $search['value'] . "%")
                    ->orWhere("$table.authid", 'like', "%" . $search['value'] . "%")
                    ->orWhere("admins.user", 'like', "%" . $search['value'] . "%")
                    ->orWhere('admins.authid', 'like', "%" . $search['value'] . "%");
            });
        }

        foreach ($order as $v) {
            $columnIndex = $v['column'];
            $columnName = $columns[$columnIndex]['name'];
            $direction = $v['dir'] === 'asc' ? 'ASC' : 'DESC';

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

    public function getCounts(string $dbname, array &$excludeAdmins = [], bool $wasAll = false): array
    {
        $db = dbal()->database($dbname);

        $bansCount = $db->table('bans')->select()->innerJoin('admins')->on(["bans.aid" => 'admins.aid']);
        $mutesCount = $db->table('comms')->select()->innerJoin('admins')->on(["comms.aid" => 'admins.aid'])->where('type', 1);
        $gagsCount = $db->table('comms')->select()->innerJoin('admins')->on(["comms.aid" => 'admins.aid'])->where('type', 2);

        $steamid32 = [];

        foreach ($excludeAdmins as $admin) {
            $steamid32[] = steam()->steamid($admin)->RenderSteam2();
        }

        $bansCount->andWhere([
            'admins.authid' => [
                'NOT IN' => new Parameter(($steamid32))
            ]
        ]);
        $mutesCount->andWhere([
            'admins.authid' => [
                'NOT IN' => new Parameter(($steamid32))
            ]
        ]);
        $gagsCount->andWhere([
            'admins.authid' => [
                'NOT IN' => new Parameter(($steamid32))
            ]
        ]);

        try {
            $uniqueAdmins = $db->table('admins')->select()->distinct()->columns('authid');

            $newAdmins = [];
            foreach ($uniqueAdmins->fetchAll() as $admin) {
                if ($admin['authid'] === 'STEAM_ID_SERVER' || $admin['authid'] === 'CONSOLE')
                    continue;

                $steamid64 = steam()->steamid($admin['authid'])->ConvertToUInt64();

                if (!in_array($steamid64, $excludeAdmins)) {
                    $excludeAdmins[] = $steamid64;
                    $newAdmins[] = $steamid64;
                }
            }

            return [
                'bans' => $bansCount->count(),
                'mutes' => $mutesCount->count(),
                'gags' => $gagsCount->count(),
                'admins' => sizeof($newAdmins)
            ];
        } catch (StatementException $e) {
            logs()->error($e);

            return [
                'bans' => 0,
                'mutes' => 0,
                'gags' => 0,
                'admins' => 0
            ];
        }
    }


    private function getSteamIds64(array $results): array
    {
        $steamIds64 = [];

        foreach ($results as $result) {
            try {
                if (!isset($steamIds64[$result['authid']])) {
                    $steamId64 = steam()->steamid($result['authid'])->ConvertToUInt64();
                    $steamIds64[$result['authid']] = $steamId64;
                }

                if (!isset($steamIds64[$result['admin_steam']])) {
                    $steamId64 = steam()->steamid($result['admin_steam'])->ConvertToUInt64();
                    $steamIds64[$result['admin_steam']] = $steamId64;
                }
            } catch (\InvalidArgumentException $e) {
                logs()->error($e);
                unset($result);
            }
        }

        return $steamIds64;
    }

    private function mapUsersDataToResult(array $results, array $usersData): array
    {
        $mappedResults = [];

        foreach ($results as $result) {
            $steamId32 = $result['authid'];

            if (isset($usersData[$steamId32])) {
                $user = $usersData[$steamId32];
                $result['authid'] = $usersData[$steamId32]->steamid;
                $result['avatar'] = $user->avatar;
            } else {
                $result['avatar'] = url('assets/img/no_avatar.webp')->get();
            }

            $result['user_url'] = url('profile/search/' . $result['authid'])->addParams([
                "else-redirect" => "https://steamcommunity.com/profiles/" . $result['authid']
            ])->get();

            $adminSteam = $result['admin_steam'];

            if (isset($usersData[$adminSteam])) {
                $user = $usersData[$adminSteam];
                $result['admin_steam'] = $usersData[$adminSteam]->steamid;
                $result['admin_avatar'] = $user->avatar;

                $result['admin_url'] = url('profile/search/' . $result['admin_steam'])->addParams([
                    "else-redirect" => "https://steamcommunity.com/profiles/" . $result['admin_steam']
                ])->get();
            } else {
                $result['admin_avatar'] = url('assets/img/no_avatar.webp')->get();
            }

            $mappedResults[] = $result;
        }

        return $mappedResults;
    }

    public function getName(): string
    {
        return "MaterialAdmin";
    }
}