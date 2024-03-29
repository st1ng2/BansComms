<?php

namespace Flute\Modules\BansComms\Http\Controllers\Views;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\BansComms\Exceptions\ServerNotFoundException;
use Flute\Modules\BansComms\Services\BansService;

class ViewBansController extends AbstractController
{
    protected BansService $bansService;

    public function __construct(BansService $bansService)
    {
        $this->bansService = $bansService;
    }

    public function index( FluteRequest $fluteRequest )
    {
        $sid = (int) $fluteRequest->input("sid", null);

        try {
            $data = $this->bansService->generateTable($sid);

            return view('Modules/BansComms/Resources/views/bans', [
                'bans' => $data,
                'servers' => $this->bansService->getServerModes()
            ]);
        } catch (ServerNotFoundException $e) {
            return $this->error(__('banscomms.server_not_found'), 404);
        }
    }
}