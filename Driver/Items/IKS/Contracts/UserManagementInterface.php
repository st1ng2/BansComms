<?php

namespace Flute\Modules\BansComms\Driver\Items\IKS\Contracts;

interface UserManagementInterface
{
    public function banUser($steamid, $reason, $time = 0, $type = "bans"): bool;
    public function unbanUser($bid, $type = "bans"): bool;
}