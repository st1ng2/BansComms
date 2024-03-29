<?php

namespace Flute\Modules\BansComms\Http\Controllers\API\Admin;

use Flute\Core\Admin\Http\Middlewares\HasPermissionMiddleware;
use Flute\Core\Support\AbstractController;
use Flute\Core\Support\FluteRequest;
use Flute\Modules\BansComms\Services\AdminBansCommsService;
use Symfony\Component\HttpFoundation\Response;

class AdminBansCommsController extends AbstractController
{
    protected AdminBansCommsService $service;

    public function __construct(AdminBansCommsService $service)
    {
        $this->service = $service;
        HasPermissionMiddleware::permission(['admin', 'admin.servers']);
    }

    public function store(FluteRequest $request): Response
    {
        try {
            $this->validate($request);

            $this->service->store(
                $request->mod,
                $request->dbname,
                $request->additional ?? '[]',
                (int) $request->sid
            );

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(FluteRequest $request, $id): Response
    {
        try {
            $this->service->delete((int) $id);

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(FluteRequest $request, $id): Response
    {
        try {
            $this->validate($request);

            $this->service->update(
                (int) $id,
                $request->mod,
                $request->dbname,
                $request->additional ?? '[]',
                (int) $request->sid
            );

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function validate( FluteRequest $request )
    {
        if( empty( $request->input('mod') ) || empty( $request->input('dbname') ) )
            throw new \Exception(__('banscomms.params_empty'));
    }
}