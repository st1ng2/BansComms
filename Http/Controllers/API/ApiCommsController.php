<?php

namespace Flute\Modules\BansComms\Http\Controllers\API;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\BansComms\Services\CommsService;

class ApiCommsController extends AbstractController
{
    protected CommsService $commsService;

    public function __construct(CommsService $commsService)
    {
        $this->commsService = $commsService;
    }

    public function getUserData(FluteRequest $request, $id, $sid)
    {
        $page = ($request->get("start", 1) + $request->get('length')) / $request->get('length');
        $draw = (int) $request->get("draw", 1);
        $columns = $request->get("columns", []);
        $search = $request->get("search", []);
        $order = $request->get("order", []);

        $length = (int) $request->get('length') > 100 ?: (int) $request->get('length');

        try {
            $data = $this->commsService->getUserData(
                user()->get((int) $id),
                $page,
                $length,
                $draw,
                $columns,
                $search,
                $order,
                $sid,
            );

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getData(FluteRequest $request, $sid)
    {
        $page = ($request->get("start", 1) + $request->get('length')) / $request->get('length');
        $draw = (int) $request->get("draw", 1);
        $columns = $request->get("columns", []);
        $search = $request->get("search", []);
        $order = $request->get("order", []);

        $length = (int) $request->get('length') > 100 ?: (int) $request->get('length');

        try {
            $data = $this->commsService->getData(
                $page,
                $length,
                $draw,
                $columns,
                $search,
                $order,
                $sid,
            );

            return $this->json($data);
        } catch (\Exception $e) {
            if (is_debug()) {
                return $this->error($e->getMessage(), 500);
            }

            return $this->error(__('def.unknown_error'), 500);
        }
    }
}