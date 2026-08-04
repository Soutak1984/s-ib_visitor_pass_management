<?php

/**
 * Created by PhpStorm.
 * User: dipok
 * Date: 20/4/20
 * Time: 2:41 PM
 */

namespace App\Http\Controllers\Api\v1;

use App\Models\Setting;
use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\SettingResource;
use App\Http\Resources\v1\VisitorResource;
use App\Http\Services\PreRegister\PreRegisterService;
use App\Http\Services\Visitor\VisitorService;
use App\Models\PreRegister;

class DashboardController extends Controller
{
    use ApiResponse;
    protected $visitorService;
    protected $preRegisterService;

    public function __construct(VisitorService $visitorService,PreRegisterService $preRegisterService)
    {
        $this->visitorService = $visitorService;
        $this->preRegisterService = $preRegisterService;
    }

    public function index()
    {

        $i            = 1;
        $visitors = [];
        $total_visitors = 0;
        $total_pre_registers = 0;

        $visitingDetails = $this->visitorService->all();
        $total_visitors = $visitingDetails->count();

        $total_pre_registers = $this->preRegisterService->all()->count();
        

        $visitingDetails = $this->visitorService->take(10);
        if (!blank($visitingDetails)) {
            foreach ($visitingDetails as $visitingDetail) {
                $visitors[$i]['id']          = $visitingDetail->id;
                $visitors[$i]['reg_no']      = $visitingDetail->reg_no;
                $visitors[$i]['name']        = $visitingDetail->visitor->name;
                $visitors[$i]['image']       = $visitingDetail->images;
                $visitors[$i]['status']      = $visitingDetail->status;
                $visitors[$i]['status_name'] = trans('visitor_statuses.' . $visitingDetail->status);
                $visitors[$i]['checkin_at']  = blank($visitingDetail->checkin_at) ? "" : date('h:i A',strtotime($visitingDetail->checkin_at));
                $visitors[$i]['checkout_at'] = blank($visitingDetail->checkout_at) ? "" : date('h:i A',strtotime($visitingDetail->checkout_at));
                $i++;
            }
            $visitors = VisitorResource::collection($visitors);
        }

        return $this->successResponse([
            'status' => 200,
            'visitor' => $visitors,
            'total_visitors' => $total_visitors,
            'total_pre_registers' => $total_pre_registers,

        ], 200);
    }
}
