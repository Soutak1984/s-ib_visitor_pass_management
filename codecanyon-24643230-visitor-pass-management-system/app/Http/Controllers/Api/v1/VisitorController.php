<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Requests\Api\CheckVisitorRequest;
use App\Models\Visitor;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\VisitingDetails;
use App\Http\Controllers\Controller;
use App\Http\Requests\VisitorRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\v1\VisitorResource;
use App\Notifications\VisitorConfirmation;
use App\Http\Requests\VisitorUpdateRequest;
use App\Http\Services\Visitor\VisitorService;
use App\Http\Resources\v1\SingleVisitorResource;


class VisitorController extends Controller
{
    use ApiResponse;

    protected $visitorService;


    public function __construct(VisitorService $visitorService)
    {
        $this->data['sitetitle'] = 'Visitors';
        $this->middleware('auth:api')->except('checkin','checkinCheck', 'checkout', 'findVisitor');
        $this->middleware(['permission:visitors'])->only('index');
        $this->middleware(['permission:visitors_create'])->only('create', 'store');
        $this->middleware(['permission:visitors_edit'])->only('edit', 'update');
        $this->middleware(['permission:visitors_delete'])->only('destroy');
        $this->middleware(['permission:visitors_show'])->only('show');

        $this->visitorService = $visitorService;
    }
    public function index()
    {
        try {
            $visitingDetails = $this->visitorService->all();
            $i            = 1;
            $visitors = [];
            if (!blank($visitingDetails)) {
                foreach ($visitingDetails as $visitingDetail) {
                    $visitors[$i]['id'] = $visitingDetail->id;
                    $visitors[$i]['reg_no'] = $visitingDetail->reg_no;
                    $visitors[$i]['name'] = $visitingDetail->visitor->name;
                    $visitors[$i]['image'] = $visitingDetail->images;
                    $visitors[$i]['status'] = $visitingDetail->status;
                    $visitors[$i]['status_name'] = trans('visitor_statuses.' . $visitingDetail->status);
                    $visitors[$i]['visitor_id'] = $visitingDetail->visitor->id;
                    $visitors[$i]['checkin_at']                 = blank($visitingDetail->checkin_at) ? "" : date('h:i A', strtotime($visitingDetail->checkin_at));
                    $visitors[$i]['checkout_at']                = blank($visitingDetail->checkout_at) ? "" : date('h:i A', strtotime($visitingDetail->checkout_at));

                    $i++;
                }
                $visitors = VisitorResource::collection($visitors);
                return $this->successResponse(['status' => 200, 'message' => "success", 'visitor' => $visitors]);
            } else {
                return $this->errorResponse(['status' => 401, 'message' => "The data not found", 'visitor' => []]);
            }
        } catch (\Exception $e) {
            return $this->errorResponse(['status' => 500, 'message' => $e->getMessage(), 'visitor' => []]);
        }
    }

    public function show($id)
    {
        $visitingDetails = $this->visitorService->find($id);
        if (!blank($visitingDetails)) {
            $visitor['name']                       = $visitingDetails->visitor->name;
            $visitor['first_name']                 = $visitingDetails->visitor->first_name;
            $visitor['last_name']                  = $visitingDetails->visitor->last_name;
            $visitor['email']                      = blank($visitingDetails->visitor->email) ? "" : $visitingDetails->visitor->email;
            $visitor['reg_no']                     = $visitingDetails->reg_no;
            $visitor['phone']                      = $visitingDetails->visitor->phone;
            $visitor['country_code']               = $visitingDetails->visitor->country_code;
            $visitor['country_code_name']          = $visitingDetails->visitor->country_code_name;
            $visitor['image']                      = $visitingDetails->images;
            $visitor['gender']                     = $visitingDetails->visitor->gender;
            $visitor['gender_name']                = trans('genders.' . $visitingDetails->visitor->gender);
            $visitor['company_name']               = blank($visitingDetails->company_name) ? "" : $visitingDetails->company_name;
            $visitor['national_identification_no'] = $visitingDetails->visitor->national_identification_no;
            $visitor['address']                    = blank($visitingDetails->visitor->address) ? "" : $visitingDetails->visitor->address;
            $visitor['employee']                   = $visitingDetails->employee->name;
            $visitor['purpose']                    = $visitingDetails->purpose;
            $visitor['entry_gate_number']          = blank($visitingDetails->entry_gate_number) ? "" : $visitingDetails->entry_gate_number;
            $visitor['date']                       = blank($visitingDetails->created_at) ? "" : date('Y-m-d', strtotime($visitingDetails->created_at));
            $visitor['checkin_at']                 = blank($visitingDetails->checkin_at) ? "" : date('h:i A', strtotime($visitingDetails->checkin_at));
            $visitor['checkout_at']                = blank($visitingDetails->checkout_at) ? "" : date('h:i A', strtotime($visitingDetails->checkout_at));
            $visitor['raw_checkin_at']             = $visitingDetails->checkin_at;
            $visitor['raw_checkout_at']            = $visitingDetails->checkout_at;
            $visitor['status']                     = $visitingDetails->status;
            $visitor['status_name']                = trans('visitor_statuses.' . $visitingDetails->status);

            $visitingDetails = new VisitorResource($visitor);
            return $this->successResponse(['status' => 200, 'message' => "Success", 'visitor' => $visitingDetails]);
        } else {
            return $this->errorResponse(['status' => 401, 'message' => "The data not found", 'visitor' => (object)[]]);
        }
    }

    public function store(Request $request)
    {
        $validator = new VisitorRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if (!$validator->fails()) {
            $visitingDetails = $this->visitorService->make($request);
            if ($visitingDetails) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'Visitor Created Successfully.',
                ], 200);
            }
        } else {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = new VisitorRequest($request->visitor_id);
        $validator = Validator::make($request->all(), $validator->rules());
        $validator->after(function ($validator) {
            if (!$this->checkUniqueEmail(request('email'), request('visitor_id'))) {
                $validator->errors()->add('email', 'This Email Already Exists');
            }
            if (!$this->checkUniquePhone(request('phone'), request('visitor_id'))) {
                $validator->errors()->add('phone', 'This Phone Number Already Exists');
            }
            if (!$this->checkUniqueNid(request('national_identification_no'), request('visitor_id'))) {
                $validator->errors()->add('national_identification_no', 'This NID  Already Exists');
            }
        });

        if (!$validator->fails()) {
            $visitingDetails = $this->visitorService->update($request, $id);
            if ($visitingDetails) {
                return response()->json([
                    'status'  => 200,
                    'message' => 'Visitor Updated Successfully.',
                ], 200);
            }
        } else {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }
    }

    public function search($keyWord)
    {

        if (auth()->user()->getrole->name == 'Employee') {
            $visitingDetails = VisitingDetails::with('visitor')->where(function ($query) use ($keyWord) {
                $query->whereHas('visitor', function ($q) use ($keyWord) {
                    $q->where('phone', 'like', '%' . $keyWord . '%');
                    $q->orWhere('email', 'like', '%' . $keyWord . '%');
                    $q->orWhere('national_identification_no', 'like', '%' . $keyWord . '%');
                    $q->orWhere('first_name', 'like', '%' . $keyWord . '%');
                    $q->orWhere('last_name', 'like', '%' . $keyWord . '%');
                });
                $query->orWhere('reg_no', 'like', '%' . $keyWord . '%');
            })->where(['employee_id' => auth()->user()->employee->id])->orderBy('id', 'desc')->get();
        } else {
            $visitingDetails = VisitingDetails::with('visitor')->where(function ($query) use ($keyWord) {
                $query->whereHas('visitor', function ($q) use ($keyWord) {
                    $q->where('phone', 'like', '%' . $keyWord . '%');
                    $q->orWhere('email', 'like', '%' . $keyWord . '%');
                    $q->orWhere('national_identification_no', 'like', '%' . $keyWord . '%');
                });
                $query->orWhere('reg_no', 'like', '%' . $keyWord . '%');
            })->orderBy('id', 'desc')->get();
        }


        $visitor = [];
        if ($visitingDetails) {
            $i = 1;
            foreach ($visitingDetails as $visitingDetail) {
                $visitor[$i]['id']          = $visitingDetail->id;
                $visitor[$i]['name']        = $visitingDetail->visitor->name;
                $visitor[$i]['reg_no']      = $visitingDetail->reg_no;
                $visitor[$i]['image']       = $visitingDetail->images;
                $visitor[$i]['status']      = $visitingDetail->status;
                $visitor[$i]['status_name'] = trans('visitor_statuses.' . $visitingDetail->status);
                $visitor[$i]['visitor_id']  = $visitingDetail->visitor->id;
                $visitor[$i]['checkin_at']  = blank($visitingDetail->checkin_at) ? "" : date('h:i A', strtotime($visitingDetail->checkin_at));
                $visitor[$i]['checkout_at'] = blank($visitingDetail->checkout_at) ? "" : date('h:i A', strtotime($visitingDetail->checkout_at));

                $i++;
            }
            return $this->successResponse(['status' => 200, 'message' => 'success', 'visitor' => array_values($visitor)]);
        } else {
            return $this->errorResponse(['status' => 401, 'message' => "No Visiting Details Found", 'visitor' => []]);
        }
    }


    public function changeStatus($id, $status)
    {
        $visitor         = VisitingDetails::findOrFail($id);
        $visitor->status = $status;
        $visitor->checkin_at = date('y-m-d H:i');
        $visitor->save();
        try {
            $visitor->visitor->notify(new VisitorConfirmation($visitor));
            return $this->successResponse(['status' => 200, 'message' => 'Visitor Checked-In Successfully.']);
        } catch (\Exception $e) {
            return $this->errorResponse(['status' => 401, 'message' => $e->getMessage()]);
        }
    }

    public function checkinCheck(Request $request)
    {
        $validator = new CheckVisitorRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if (!$validator->fails()) {
                return $this->successResponse(['status' => 200, 'message' => 'true', 'visitor' => (object)[]]);
        } else {
            return $this->errorResponse(['status' => 422, 'message' => $validator->errors(), 'visitor' => (object)[]]);
        }
    }


    public function checkin(Request $request)
    {
        $validator = new VisitorRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if (!$validator->fails()) {
            if (request('visitor_old') == 1) {
                $visitingDetails = $this->visitorService->makePrevious($request);
            } else {
                $visitingDetails = $this->visitorService->make($request);
            }

            if ($visitingDetails) {
                $visitor['name']              = $visitingDetails->visitor->name;
                $visitor['phone']             = $visitingDetails->visitor->phone;
                $visitor['country_code']      = $visitingDetails->visitor->country_code;
                $visitor['country_code_name'] = $visitingDetails->visitor->country_code_name;
                $visitor['reg_no']            = $visitingDetails->reg_no;
                $visitor['image']             = $visitingDetails->images;
                $visitor['employee']          = $visitingDetails->employee->name;
                $visitor['site_name']         = setting('site_name');
                $visitor['site_email']        = setting('site_email');
                $visitor['site_address']      = setting('site_address');

                $visitingDetails = new VisitorResource($visitor);
                return $this->successResponse(['status' => 200, 'message' => 'Success', 'visitor' => $visitingDetails]);
            }
        } else {
            return $this->errorResponse(['status' => 422, 'message' => $validator->errors(), 'visitor' => (object)[]]);
        }
    }

    public function checkout($id)
    {
        $visitingDetail = VisitingDetails::where('reg_no', $id)->first();
        if ($visitingDetail) {
            if (blank($visitingDetail->checkout_at)) {
                if (!blank($visitingDetail->checkin_at)) {
                    $visitingDetail->checkout_at = date('y-m-d H:i');
                    $visitingDetail->save();
                    return $this->successResponse(['status' => 200, 'message' => 'Visitor Checked-Out Successfully.']);
                } else {
                    return $this->errorResponse(['status' => 404, 'message' => 'Did Not Checked In Yet !']);
                }
            } else {
                return $this->errorResponse(['status' => 404, 'message' => 'Already Checked Out !']);
            }
        } else {
            return $this->errorResponse(['status' => 404, 'message' => 'Visitor Not Found']);
        }
    }

    public function destroy($id)
    {
        $this->visitorService->delete($id);
        return $this->successResponse(['status' => 200, 'message' => 'Visitor Deleted Successfully.']);
    }

    public function findVisitor(Request $request)
    {

        $validator = Validator::make(
            $request->all(),
            [
                'email' => [
                    'required',
                ],
            ],
            [
                'email.required' => 'The email or phone field is required. ',
            ]
        );
        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }

        $visitor = Visitor::where([['is_pre_register', false], ['email', request()->email]])
            ->orWhere([['is_pre_register', false], ['phone', request()->email]])
            ->orWhere([['is_pre_register', false], ['national_identification_no', request()->email]])
            ->first();
        if (!empty($visitor)) {

            $visitor['first_name'] = $visitor->first_name;
            $visitor['last_name'] = $visitor->last_name;
            $visitor['name'] = $visitor->first_name;
            $visitor['email'] = $visitor->email;
            $visitor['phone'] = $visitor->phone;
            $visitor['country_code'] = $visitor->country_code;
            $visitor['country_code_name'] = $visitor->country_code_name;
            $visitor['image'] = $visitor->images;
            $visitor['gender'] = $visitor->gender;
            $visitor['national_identification_no'] = $visitor->national_identification_no;
            $visitor['address'] = $visitor->address;
            $visitor['status'] = $visitor->status;
            $visitor['created_at'] = date('y-m-d', strtotime($visitor->created_at));
            $visitor = new SingleVisitorResource($visitor);
            return $this->successResponse(['status' => 200, 'message' => "success", 'visitor' => $visitor, 'visited_before' => 'Yes']);
        } else {
            return $this->errorResponse(['status' => 404, 'message' => 'Visitor Not Found', 'visitor' => (object)[], 'visited_before' => 'No']);
        }
    }

    public function checkUniqueEmail($email, $id)
    {
        $user = Visitor::where('email', $email)->first();

        if ($user) {
            if ($user->id == $id) {
                return true;
            }
            return false;
        } else {
            return true;
        }
    }
    public function checkUniquePhone($phone, $id)
    {
        $user = Visitor::where('phone', $phone)->first();

        if ($user) {
            if ($user->id == $id) {
                return true;
            }
            return false;
        } else {
            return true;
        }
    }
    public function checkUniqueNid($nid, $id)
    {
        $user = Visitor::where('national_identification_no', $nid)->first();

        if ($user) {
            if ($user->id == $id) {
                return true;
            }
            return false;
        } else {
            return true;
        }
    }
}
