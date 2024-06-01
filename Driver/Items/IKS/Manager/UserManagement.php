<?php

namespace Flute\Modules\BansComms\Driver\Items\IKS\Manager;

use Flute\Modules\BansComms\Driver\Items\IKS\Contracts\UserManagementInterface;

class UserManagement implements UserManagementInterface
{
    public function banUser($steamid, $reason, $time = 0, $type = "bans"): bool
    {
        return false;
    }
    
    public function unbanUser($bid, $type = "bans"): bool
    {
        return false;
    }
}