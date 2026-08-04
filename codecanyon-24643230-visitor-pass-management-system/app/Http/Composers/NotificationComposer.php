<?php

namespace App\Http\Composers;

use Illuminate\View\View;
use App\Enums\VisitorStatus;
use App\Models\VisitingDetails;

class NotificationComposer
{
    public function compose(View $view)
    {
        $latestVisitors = [];
        if(auth()->user()->myrole == 2){
            $latestVisitors = VisitingDetails::where('status', VisitorStatus::PENDDING)->where(['employee_id' => auth()->user()->employee->id])->orderBy('id', 'desc')->get();
        }
        $view->with('latestVisitors', $latestVisitors);
    }
}
