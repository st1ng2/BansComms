<?php

namespace Flute\Modules\BansComms\Http\Controllers\Views;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\BansComms\Exceptions\ServerNotFoundException;
use Flute\Modules\BansComms\Services\CommsService;

class ViewCommsController extends AbstractController
{
    protected CommsService $commsService;

    public function __construct(CommsService $commsService)
    {
        $this->commsService = $commsService;
    }

    public function index( FluteRequest $fluteRequest )
    {
        $sid = (int) $fluteRequest->input("sid", null);

        try {
            $data = $this->commsService->generateTable($sid);

            return view('Modules/BansComms/Resources/views/comms', [
                'comms' => $data,
                'servers' => $this->commsService->getServerModes()
            ]);
        } catch (ServerNotFoundException $e) {
            return $this->error(__('banscomms.server_not_found'), 404);
        }
    }
}