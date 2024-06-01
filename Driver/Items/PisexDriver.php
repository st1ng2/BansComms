<?php

namespace Flute\Modules\BansComms\Driver\Items;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableBuilder;
use Flute\Core\Table\TableColumn;
use Flute\Core\Table\TablePreparation;
use Flute\Modules\BansComms\Contracts\DriverInterface;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\Fragment;
use Spiral\Database\Injection\Parameter;

class PisexDriver implements DriverInterface
{
    public function getCommsColumns(TableBuilder $tableBuilder)
    {
        $tableBuilder->addColumn((new TableColumn('source', __('banscomms.table.type')))
            ->setRender("{{ICON_TYPE}}", $this->typeFormatRender()));

        $tableBuilder->addColumn((new TableColumn('user_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('avatar', 'name', __('banscomms.table.loh'), 'user_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('created', __('banscomms.table.created')))->setDefaultOrder()
                ->setRender("{{CREATED}}", $this->dateFormatRender()),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
        ]);

        $tableBuilder->addColumn((new TableColumn('admin_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('admin_avatar', 'admin_name', __('banscomms.table.admin'), 'admin_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('end', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->dateFormatRender()),
            (new TableColumn('duration', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->timeFormatRender()),
        ]);
    }

    public function getBansColumns(TableBuilder $tableBuilder)
    {
        $tableBuilder->addColumn((new TableColumn('user_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('avatar', 'name', __('banscomms.table.loh'), 'user_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('created', __('banscomms.table.created')))->setDefaultOrder()
                ->setRender("{{CREATED}}", $this->dateFormatRender()),
            (new TableColumn('reason', __('banscomms.table.reason')))->setType('text'),
        ]);

        $tableBuilder->addColumn((new TableColumn('admin_url'))->setVisible(false));
        $tableBuilder->addCombinedColumn('admin_avatar', 'admin_name', __('banscomms.table.admin'), 'admin_url', true);

        $tableBuilder->addColumns([
            (new TableColumn('end', __('banscomms.table.end_date')))->setType('text')
                ->setRender("{{ENDS}}", $this->dateFormatRender()),
            (new TableColumn('duration', ''))->setType('text')->setVisible(false),
            (new TableColumn('', __('banscomms.table.length')))
                ->setSearchable(false)->setOrderable(false)
                ->setRender('{{KEY}}', $this->timeFormatRenderBans()),
        ]);
    }

    private function dateFormatRender(): string
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

    private function typeFormatRender(): string
    {
        return '
            function(data, type) {
                if (type === "display") {
                    return data == "mutes" ? `<i class="type-icon ph-bold ph-microphone-slash"></i>` : `<i class="type-icon ph-bold ph-chat-circle-dots"></i>`;
                }
                return data;
            }
        ';
    }

    private function timeFormatRender(): string
    {
        return "
            function(data, type, full) {
                let time = full[12];
                let ends = full[11];

                if (time == '0') {
                    return '<div class=\"ban-chip bans-forever\">'+ t(\"banscomms.table.forever\") +'</div>';
                } else if (Date.now() >= ends * 1000 && time != '0') {
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
                let time = full[11];
                let ends = full[10];

                if (time == '0') {
                    return '<div class=\"ban-chip bans-forever\">'+ t(\"banscomms.table.forever\") +'</div>';
                } else if (Date.now() >= ends * 1000 && time != '0') {
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

        if (!$steam)
            return [];

        $select = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'bans')
            ->where('bans.steamid', (int) $steam->value);

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
                    'end',
                    'duration',
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

        if (!$steam)
            return [];

        list($selectMutes, $selectGags) = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms');

        $selectMutes->where('mutes.steamid', (int) $steam->value);
        $selectGags->where('gags.steamid', (int) $steam->value);

        // Fetch results separately for mutes and gags
        $resultMutes = $selectMutes->fetchAll();
        $resultGags = $selectGags->fetchAll();

        // Merge and slice the results according to pagination
        $mergedResults = array_merge($resultMutes, $resultGags);
        $paginatedResults = array_slice($mergedResults, ($page - 1) * $perPage, $perPage);

        $steamIds = $this->getSteamIds64($paginatedResults);
        $usersData = steam()->getUsers($steamIds);

        $paginatedResults = $this->mapUsersDataToResult($paginatedResults, $usersData);

        return [
            'draw' => $draw,
            'recordsTotal' => count($mergedResults),
            'recordsFiltered' => count($mergedResults),
            'data' => TablePreparation::normalize(
                [
                    'source',
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
                    'end',
                    'duration',
                    ''
                ],
                $paginatedResults
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
        list($selectMutes, $selectGags) = $this->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms');

        // Fetch results separately for mutes and gags
        $resultMutes = $selectMutes->fetchAll();
        $resultGags = $selectGags->fetchAll();

        // Merge and slice the results according to pagination
        $mergedResults = array_merge($resultMutes, $resultGags);
        $paginatedResults = array_slice($mergedResults, ($page - 1) * $perPage, $perPage);

        $steamIds = $this->getSteamIds64($paginatedResults);
        $usersData = steam()->getUsers($steamIds);

        $paginatedResults = $this->mapUsersDataToResult($paginatedResults, $usersData);

        return [
            'draw' => $draw,
            'recordsTotal' => count($mergedResults),
            'recordsFiltered' => count($mergedResults),
            'data' => TablePreparation::normalize(
                [
                    'source',
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
                    'end',
                    'duration',
                    ''
                ],
                $paginatedResults
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
                    'end',
                    'duration',
                    ''
                ],
                $result
            )
        ];
    }

    private function prepareSelectQuery(Server $server, string $dbname, array $columns, array $search, array $order, string $table = 'bans')
    {
        if ($table === 'comms') {
            // Initialize an array to hold select queries
            $selectQueries = [];

            foreach (['mutes', 'gags'] as $tableName) {
                $select = $this->buildSelectQuery($dbname, $tableName, $columns, $search, $order);
                array_push($selectQueries, $select);
            }

            return $selectQueries;
        } else {
            return $this->buildSelectQuery($dbname, "bans", $columns, $search, $order);
        }
    }

    private function buildSelectQuery(string $dbname, string $tableName, array $columns, array $search, array $order)
    {
        $select = dbal()->database($dbname)->table($tableName)->select()->columns([
            "$tableName.*",
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
                $select->where("$tableName.name", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.steamid", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.admin_steamid", 'like', '%' . $search['value'] . '%')
                    ->orWhere("$tableName.admin_name", 'like', '%' . $search['value'] . '%')
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

        return $select;
    }

    public function getCounts(string $dbname, array &$excludeAdmins = []): array
    {
        $db = dbal()->database($dbname);

        $bansCount = $db->table('bans')->select()->innerJoin('admins');
        $mutesCount = $db->table('mutes')->select()->innerJoin('admins');
        $gagsCount = $db->table('gags')->select()->innerJoin('admins');

        if (!empty($excludeAdmins)) {
            $bansCount->andWhere([
                'admins.steamid' => [
                    'NOT IN' => new Parameter($excludeAdmins)
                ]
            ]);
            $mutesCount->andWhere([
                'admins.steamid' => [
                    'NOT IN' => new Parameter($excludeAdmins)
                ]
            ]);
            $gagsCount->andWhere([
                'admins.steamid' => [
                    'NOT IN' => new Parameter($excludeAdmins)
                ]
            ]);
        }

        try {
            $uniqueAdmins = $db->table('admins')->select()->distinct()->columns('steamid');

            $newAdmins = [];
            foreach ($uniqueAdmins->fetchAll() as $admin) {
                if (!in_array($admin['steamid'], $excludeAdmins)) {
                    $excludeAdmins[] = $admin['steamid'];
                    $newAdmins[] = $admin['steamid'];
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
                if (!isset($steamIds64[$result['steamid']])) {
                    $steamId64 = steam()->steamid($result['steamid'])->ConvertToUInt64();
                    $steamIds64[$result['steamid']] = $steamId64;
                }

                if (!isset($steamIds64[$result['admin_steamid']])) {
                    $steamId64 = steam()->steamid($result['admin_steamid'])->ConvertToUInt64();
                    $steamIds64[$result['admin_steamid']] = $steamId64;
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
            $steamId32 = $result['steamid'];

            if (isset($usersData[$steamId32])) {
                $user = $usersData[$steamId32];
                $result['steamid'] = $usersData[$steamId32]->steamid;
                $result['avatar'] = $user->avatar;
            } else {
                $result['avatar'] = url('assets/img/no_avatar.webp')->get();
            }

            $result['user_url'] = url('profile/search/' . $result['steamid'])->addParams([
                "else-redirect" => "https://steamcommunity.com/profiles/" . $result['steamid']
            ])->get();

            $adminSteam = $result['admin_steamid'];

            if (isset($usersData[$adminSteam])) {
                $user = $usersData[$adminSteam];
                $result['admin_steamid'] = $usersData[$adminSteam]->steamid;
                $result['admin_avatar'] = $user->avatar;

                $result['admin_url'] = url('profile/search/' . $result['admin_steamid'])->addParams([
                    "else-redirect" => "https://steamcommunity.com/profiles/" . $result['admin_steamid']
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
        return "PisexAdmin";
    }
}