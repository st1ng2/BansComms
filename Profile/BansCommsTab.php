<?php

namespace Flute\Modules\BansComms\Profile;

use Flute\Core\Contracts\ProfileTabInterface;
use Flute\Core\Database\Entities\User;
use Flute\Modules\BansComms\Services\BansCommsService;

class BansCommsTab implements ProfileTabInterface
{
    public function render(User $user)
    {
        $bansCommsService = app(BansCommsService::class);
        $sid = request()->input('sid');

        return render(mm('BansComms', 'Resources/views/profile/banscommstab'), [
            'bans' => $bansCommsService->generateTable($sid, 'bans', $user),
            'comms' => $bansCommsService->generateTable($sid, 'comms', $user),
            'user' => $user,
            'servers' => $bansCommsService->getServerModes(),
            'steam' => $user->getSocialNetwork('Steam') ?? $user->getSocialNetwork('HttpsSteam')
        ]);
    }

    public function getSidebarInfo()
    {
        return [
            'icon' => 'ph ph-gavel',
            'name' => 'banscomms.profile.head',
        ];
    }

    public function getKey()
    {
        return 'banscomms';
    }
}