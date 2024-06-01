<?php

namespace Flute\Modules\BansComms\Driver\Items;

use Flute\Core\Database\Entities\Server;
use Flute\Core\Database\Entities\User;
use Flute\Core\Table\TableBuilder;
use Flute\Core\Table\TablePreparation;
use Flute\Modules\BansComms\Contracts\DriverInterface;
use Flute\Modules\BansComms\Driver\Items\IKS\ColumnManager\TableColumnManager;
use Flute\Modules\BansComms\Driver\Items\IKS\Formatter\ColumnFormatter;
use Flute\Modules\BansComms\Driver\Items\IKS\Manager\UserManagement;
use Flute\Modules\BansComms\Driver\Items\IKS\QueryBuilder\QueryBuilder;
use Spiral\Database\Exception\StatementException;
use Spiral\Database\Injection\Parameter;


class IKSDriver implements DriverInterface
{
    private $sid;
    private $columnManager;
    private $queryBuilder;
    private $userManagement;

    public function __construct(array $config = [])
    {
        $this->sid = isset($config['sid']) ? $config['sid'] : 1;
        $this->columnManager = new TableColumnManager(new ColumnFormatter);
        $this->queryBuilder = new QueryBuilder($this->sid);
        $this->userManagement = new UserManagement;
    }

    public function getCommsColumns(TableBuilder $tableBuilder)
    {
        return $this->columnManager->getCommsColumns($tableBuilder);
    }

    public function getBansColumns(TableBuilder $tableBuilder)
    {
        return $this->columnManager->getBansColumns($tableBuilder);
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

        $select = $this->queryBuilder->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'bans')
            ->where('bans.sid', (int) $steam->value);

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
                    'time',
                    'Unbanned',
                    'UnbannedBy',
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

        list($selectMutes, $selectGags) = $this->queryBuilder->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms');

        $selectMutes->where('mutes.sid', (int) $steam->value);
        $selectGags->where('gags.sid', (int) $steam->value);

        $resultMutes = $selectMutes->fetchAll();
        $resultGags = $selectGags->fetchAll();

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
                    'time',
                    'Unbanned',
                    'UnbannedBy',
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
        list($selectMutes, $selectGags) = $this->queryBuilder->prepareSelectQuery($server, $dbname, $columns, $search, $order, 'comms');

        $resultMutes = $selectMutes->fetchAll();
        $resultGags = $selectGags->fetchAll();

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
                    'time',
                    'Unbanned',
                    'UnbannedBy',
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
        $select = $this->queryBuilder->prepareSelectQuery($server, $dbname, $columns, $search, $order);

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
                    'time',
                    'Unbanned',
                    'UnbannedBy',
                    ''
                ],
                $result
            )
        ];
    }

    public function getCounts(string $dbname, array &$excludeAdmins = []): array
    {
        $db = dbal()->database($dbname);

        $bansCount = $db->table('bans')->select()->innerJoin('admins');
        $mutesCount = $db->table('mutes')->select()->innerJoin('admins');
        $gagsCount = $db->table('gags')->select()->innerJoin('admins');

        if (!empty($excludeAdmins)) {
            $bansCount->andWhere([
                'admins.adminsid' => [
                    'NOT IN' => new Parameter(($excludeAdmins))
                ]
            ]);
            $mutesCount->andWhere([
                'admins.adminsid' => [
                    'NOT IN' => new Parameter(($excludeAdmins))
                ]
            ]);
            $gagsCount->andWhere([
                'admins.adminsid' => [
                    'NOT IN' => new Parameter(($excludeAdmins))
                ]
            ]);
        }

        try {
            $uniqueAdmins = $db->table('admins')->select()->distinct()->columns('sid');

            $newAdmins = [];
            foreach ($uniqueAdmins->fetchAll() as $admin) {
                if (!in_array($admin['sid'], $excludeAdmins)) {
                    $excludeAdmins[] = $admin['sid'];
                    $newAdmins[] = $admin['sid'];
                }
            }

            return [
                'bans' => $bansCount->count(),
                'mutes' => $mutesCount->count(),
                'gags' => $gagsCount->count(),
                'admins' => sizeof($newAdmins)
            ];
        } catch (StatementException $e) {
            // logs()->error($e);

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
                if (!isset($steamIds64[$result['sid']])) {
                    $steamId64 = steam()->steamid($result['sid'])->ConvertToUInt64();
                    $steamIds64[$result['sid']] = $steamId64;
                }

                if (!isset($steamIds64[$result['adminsid']])) {
                    $steamId64 = steam()->steamid($result['adminsid'])->ConvertToUInt64();
                    $steamIds64[$result['adminsid']] = $steamId64;
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
            $steamId32 = $result['sid'];

            if (isset($usersData[$steamId32])) {
                $user = $usersData[$steamId32];
                $result['sid'] = $usersData[$steamId32]->steamid;
                $result['avatar'] = $user->avatar;
            } else {
                $result['avatar'] = url('assets/img/no_avatar.webp')->get();
            }

            $result['user_url'] = url('profile/search/' . $result['sid'])->addParams([
                "else-redirect" => "https://steamcommunity.com/profiles/" . $result['sid']
            ])->get();

            $result['admin_url'] = url('profile/search/' . $result['adminsid'])->addParams([
                "else-redirect" => "https://steamcommunity.com/profiles/" . $result['adminsid']
            ])->get();

            $adminSteam = $result['adminsid'];

            if (isset($usersData[$adminSteam])) {
                $user = $usersData[$adminSteam];
                $result['adminsid'] = $usersData[$adminSteam]->steamid;
                $result['admin_avatar'] = $user->avatar;
            } else {
                $result['admin_avatar'] = url('assets/img/no_avatar.webp')->get();
            }

            $mappedResults[] = $result;
        }

        return $mappedResults;
    }

    public function banUser($steamid, $reason, $time = 0, $type = "bans"): bool
    {
        return $this->userManagement->banUser($steamid, $reason, $time, $type);
    }

    public function unbanUser($bid, $type = "bans"): bool
    {
        return $this->userManagement->unbanUser($bid, $type);
    }

    public function getName(): string
    {
        return "IKSAdmin";
    }
}