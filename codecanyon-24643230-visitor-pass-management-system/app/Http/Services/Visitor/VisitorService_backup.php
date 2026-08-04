<?php

namespace App\Http\Services\Visitor;

use DB;
use App\Enums\Status;
use App\Models\Booking;
use App\Models\Visitor;
use App\Models\PreRegister;
use App\Enums\VisitorStatus;
use Illuminate\Http\Request;
use App\Models\VisitingDetails;
use App\Http\Requests\VisitorRequest;
use App\Http\Services\JwtTokenService;
use App\Notifications\EmployeConfirmation;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Notifications\SendVisitorToEmployee;
use App\Http\Services\PushNotificationService;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class VisitorService
{

    public function all()
    {
        if (auth()->user()->getrole->name == 'Employee') {
            return VisitingDetails::with('visitor','employee')->where(['employee_id' => auth()->user()->employee->id])->orderBy('id', 'desc')->get();
        } else {
            return VisitingDetails::with('visitor','employee')->orderBy('id', 'desc')->get();
        }
    }
    public function take($number)
    {
        if (auth()->user()->getrole->name == 'Employee') {
            return VisitingDetails::with('visitor','employee')->where(['employee_id' => auth()->user()->employee->id])->orderBy('id', 'desc')->take($number)->get();
        } else {
            return VisitingDetails::with('visitor','employee')->orderBy('id', 'desc')->take($number)->get();
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        if (auth()->user()->getrole->name == 'Employee') {
            return VisitingDetails::where(['id' => $id, 'employee_id' => auth()->user()->employee->id])->first();
        } else {
            return VisitingDetails::find($id);
        }
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function findWhere($column, $value)
    {
        return VisitingDetails::where($column, $value)->get();
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function findWhereFirst($column, $value)
    {

        return VisitingDetails::where($column, $value)->first();
    }

    /**
     * @param int $perPage
     * @return mixed
     */
    public function paginate($perPage = 10)
    {
        return VisitingDetails::paginate($perPage);
    }

    /**
     * @param VisitorRequest $request
     * @return mixed
     */
    public function make($request)
    {
        $visitorID = DB::table('visiting_details')->max('id');
        $visitorReg = VisitingDetails::find($visitorID);
        $date = date('y-m-d');
        $data = substr($date, 0, 2);
        $data1 = substr($date, 3, 2);
        $data2 = substr($date, 6, 8);
        $today = $data2 . $data1 . $data;

        if (!blank($visitorReg)) {
            $lastentrydmy = substr($visitorReg->reg_no, 0, 6);
            if ($lastentrydmy == $today) {
                $value = substr($visitorReg->reg_no, 6);
                $value += 1;
                $reg_no = $data2 . $data1 . $data . $value;
            } else {
                $reg_no = $data2 . $data1 . $data . '1';
            }
        } else {
            $reg_no = $data2 . $data1 . $data . '1';
        }

        $input['first_name']                 = $request->input('first_name');
        $input['last_name']                  = $request->input('last_name');
        $input['email']                      = $request->input('email');
        $input['phone']                      = preg_replace("/[^0-9]/", "", $request->input('phone'));
        $input['country_code']               = $request->input('country_code');
        $input['country_code_name']          = $request->input('country_code_name');
        $input['gender']                     = $request->input('gender');
        $input['address']                    = $request->input('address');
        $input['national_identification_no'] = $request->input('national_identification_no');
        $input['is_pre_register']            = false;
        $input['status']                     = Status::ACTIVE;
        $input['creator_id']                 = 1;
        $input['creator_type']               = 'App\Models\User';
        $input['editor_type']                = 'App\Models\User';
        $input['editor_id']                  = 1;

        $file_name = 'qrcode-' . preg_replace("/[^0-9]/", "", $request->input('phone')) . '.png';
        $input['barcode']  = $file_name;
        $file = public_path('qrcode/' . $file_name);
        QRCode::size(300)->format('png')->generate(route('checkin.visitor-details', preg_replace("/[^0-9]/", "", $request->input('phone'))), $file);
        $visitor = Visitor::create($input);

        if ($visitor) {
            $visiting['reg_no']       = $reg_no;
            $visiting['purpose']      = $request->input('purpose');
            $visiting['company_name'] = $request->input('company_name');
            $visiting['employee_id']  = $request->input('employee_id');
            $visiting['visitor_id']   = $visitor->id;
            $visiting['status']       = VisitorStatus::PENDDING;
            $visiting['user_id']      = $request->input('employee_id');
            $visiting['creator_id']   = 1;
            $visiting['creator_type'] = 'App\Models\User';
            $visiting['editor_type']  = 'App\Models\User';
            $visiting['editor_id']    = 1;
            $visitingDetails          = VisitingDetails::create($visiting);
            if ($request->file('image')) {
                $visitingDetails->addMedia($request->file('image'))->toMediaCollection('visitor');
            }

            try {
                $token =  app(JwtTokenService::class)->jwtToken($visitingDetails);
                $visitingDetails->employee->user->notify(new EmployeConfirmation($visitingDetails, $token));
            } catch (\Exception $e) {
                // Using a generic exception

            }

            try {
                app(PushNotificationService::class)->sendWebNotification($visitingDetails);
            } catch (\Exception $exception) {
            }

            try {
                app(PushNotificationService::class)->sendPushNotification($visitingDetails, $visitingDetails->employee->email);
            } catch (\Exception $exception) {
            }
        } else {
            $visitingDetails = '';
        }

        return $visitingDetails;
    }

    /**
     * @param $id
     * @param VisitorRequest $request
     * @return mixed
     */
    public function update($request, $id)
    {
        $visitingDetails                     = VisitingDetails::findOrFail($id);
        $input['first_name']                 = $request->input('first_name');
        $input['last_name']                  = $request->input('last_name');
        $input['email']                      = $request->input('email');
        $input['phone']                      = preg_replace("/[^0-9]/", "", $request->input('phone'));
        $input['country_code']               = $request->input('country_code');
        $input['country_code_name']          = $request->input('country_code_name');
        $input['gender']                     = $request->input('gender');
        $input['address']                    = $request->input('address');
        $input['national_identification_no'] = $request->input('national_identification_no');
        $input['is_pre_register']            = false;
        $input['status']                     = Status::ACTIVE;

        $file_name = 'qrcode-' . preg_replace("/[^0-9]/", "", $request->input('phone')) . '.png';
        $input['barcode']  = $file_name;
        $file = public_path('qrcode/' . $file_name);
        QRCode::size(300)->format('png')->generate(route('checkin.visitor-details', preg_replace("/[^0-9]/", "", $request->input('phone'))), $file);
        $visitingDetails->visitor->update($input);

        if ($visitingDetails) {
            $visiting['purpose']      = $request->input('purpose');
            $visiting['company_name'] = $request->input('company_name');
            $visiting['employee_id']  = $request->input('employee_id');
            $visiting['visitor_id']   = $visitingDetails->visitor->id;
            $visiting['status']       = Status::ACTIVE;
            $visiting['user_id']      = $request->input('employee_id');
            $visitingDetails->update($visiting);
        }

        if ($request->file('image')) {
            $visitingDetails->media()->delete();
            $visitingDetails->addMedia($request->file('image'))->toMediaCollection('visitor');
        }
        try {
            $visitingDetails->employee->user->notify(new SendVisitorToEmployee($visitingDetails));
        } catch (\Exception $e) {
            // Using a generic exception

        }
        return $visitingDetails;
    }

    public function makePrevious($request)
    {
        $visitorID = DB::table('visiting_details')->max('id');
        $visitorReg = VisitingDetails::find($visitorID);
        $date = date('y-m-d');
        $data = substr($date, 0, 2);
        $data1 = substr($date, 3, 2);
        $data2 = substr($date, 6, 8);
        $today = $data2 . $data1 . $data;

        if (!blank($visitorReg)) {
            $lastentrydmy = substr($visitorReg->reg_no, 0, 6);
            if ($lastentrydmy == $today) {
                $value = substr($visitorReg->reg_no, 6);
                $value += 1;
                $reg_no = $data2 . $data1 . $data . $value;
            } else {
                $reg_no = $data2 . $data1 . $data . '1';
            }
        } else {
            $reg_no = $data2 . $data1 . $data . '1';
        }

        $visitor = Visitor::where('national_identification_no', $request['national_identification_no'])->first();
        $visitor->first_name = $request['first_name'];
        $visitor->last_name = $request['last_name'];
        $visitor->email = $request['email'];
        $visitor->phone = $request['phone'];
        $visitor->gender = $request['gender'];
        $visitor->address = $request['address'];
        $visitor->is_pre_register = false;
        $file_name = 'qrcode-' . preg_replace("/[^0-9]/", "", $request['phone']) . '.png';
        $visitor->barcode = $file_name;
        $file = public_path('qrcode/' . $file_name);
        QRCode::size(300)->format('png')->generate(route('checkin.visitor-details', preg_replace("/[^0-9]/", "", $request['phone'])), $file);
        $visitor->save();
        if ($visitor) {
            $visiting['reg_no'] = $reg_no;
            $visiting['purpose'] = $request->input('purpose');
            $visiting['company_name'] = $request->input('company_name');
            $visiting['employee_id'] = $request->input('employee_id');
            $visiting['visitor_id'] = $visitor->id;
            $visiting['status'] = VisitorStatus::PENDDING;
            $visiting['user_id'] = $request->input('employee_id');
            $visiting['creator_id'] = 1;
            $visiting['creator_type'] = 'App\Models\User';
            $visiting['editor_type'] = 'App\Models\User';
            $visiting['editor_id'] = 1;
            $visitingDetails = VisitingDetails::create($visiting);
            if ($request->file('image')) {
                $visitingDetails->addMedia($request->file('image'))->toMediaCollection('visitor');
            }


            try {
                $token =  app(JwtTokenService::class)->jwtToken($visitingDetails);
                $visitingDetails->employee->user->notify(new EmployeConfirmation($visitingDetails, $token));
            } catch (\Exception $e) {
                // Using a generic exception

            }

            try {
                app(PushNotificationService::class)->sendWebNotification($visitingDetails);
            } catch (\Exception $exception) {
            }

            try {
                app(PushNotificationService::class)->sendPushNotification($visitingDetails, $visitingDetails->employee->email);
            } catch (\Exception $exception) {
            }
        } else {
            $visitingDetails = '';
        }

        return $visitingDetails;
    }
    public function delete($id)
    {
        try {
            $VisitingDetails = VisitingDetails::find($id);
            // $VisitingDetails->visitor->delete();
            $VisitingDetails->delete();
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
