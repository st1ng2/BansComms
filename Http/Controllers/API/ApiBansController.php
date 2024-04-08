<?php

namespace Flute\Modules\BansComms\Http\Controllers\API;

use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\BansComms\Services\BansService;

class ApiBansController extends AbstractController
{
    protected BansService $bansService;

    public function __construct(BansService $bansService)
    {
        $this->bansService = $bansService;
    }

    public function getUserData(FluteRequest $request, $id, $sid)
    {
        $page = ($request->get("start", 1) + $request->get('length')) / $request->get('length');
        $draw = (int) $request->get("draw", 1);
        $columns = $request->get("columns", []);
        $search = $request->get("search", []);
        $order = $request->get("order", []);

        $length = (int) $request->get('length') > 100 ? : (int) $request->get('length');

        try {
            $data = $this->bansService->getUserData(
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

        $length = (int) $request->get('length') > 100 ? : (int) $request->get('length');

        try {
            $data = $this->bansService->getData(
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
}