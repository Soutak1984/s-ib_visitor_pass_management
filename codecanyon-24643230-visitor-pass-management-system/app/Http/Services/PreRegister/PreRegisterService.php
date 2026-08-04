<?php

namespace App\Http\Services\PreRegister;

use DB;
use App\Enums\Status;
use App\Models\Visitor;
use App\Models\PreRegister;
use Illuminate\Http\Request;
use App\Http\Requests\PreRegisterRequest;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Notifications\SendVisitorToEmployee;
use App\Notifications\SendInvitationToVisitors;
use Illuminate\Support\Facades\Storage;

class PreRegisterService
{

    public function all()
    {
        if (auth()->user()->getrole->name == 'Employee') {
            return PreRegister::where(['employee_id' => auth()->user()->employee->id])->orderBy('id', 'desc')->get();
        } else {
            return PreRegister::orderBy('id', 'desc')->get();
        }
    }

    /**
     * @param $id
     * @return mixed
     */
    public function find($id)
    {
        if (auth()->user()->getrole->name == 'Employee') {
            return PreRegister::where(['id' => $id, 'employee_id' => auth()->user()->employee->id])->first();
        } else {
            return PreRegister::find($id);
        }
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function findWhere($column, $value)
    {
        $result = PreRegister::where($column, $value)->get();

        return $result;
    }

    /**
     * @param $column
     * @param $value
     * @return mixed
     */
    public function findWhereFirst($column, $value)
    {
        $result = PreRegister::where($column, $value)->first();

        return $result;
    }

    /**
     * @param int $perPage
     * @return mixed
     */
    public function paginate($perPage = 10)
    {
        return PreRegister::paginate($perPage);
    }

    /**
     * @param PreRegisterRequest $request
     * @return mixed
     */
    public function make($request)
    {

        $input['first_name']                 = $request->input('first_name');
        $input['last_name']                  = $request->input('last_name');
        $input['email']                      = $request->input('email');
        $input['phone']                      = preg_replace("/[^0-9]/", "", $request->input('phone'));
        $input['country_code']               = $request->input('country_code');
        $input['country_code_name']          = $request->input('country_code_name');
        $input['national_identification_no'] = $request->input('national_identification_no');
        $input['gender']                     = $request->input('gender');
        $input['address']                    = $request->input('address');
        $input['is_pre_register']            = true;
        $input['status']                     = Status::ACTIVE;
        $input['creator_id']                 = 1;
        $input['creator_type']               = 'App\Models\User';
        $input['editor_type']                = 'App\Models\User';
        $input['editor_id']                  = 1;

        $file_name = 'qrcode-' . preg_replace("/[^0-9]/", "", $request->input('phone')) . '.png';
        $input['barcode']                    = $file_name;
        $file = public_path('qrcode/' . $file_name);
        QRCode::size(300)->format('png')->generate(route('checkin.pre-visitor-details', preg_replace("/[^0-9]/", "", $request->input('phone'))), $file);
        $visitor                     = Visitor::create($input);

        $result                       = '';
        if (auth()->user()->getrole->name == 'Employee') {
            $employee_id = auth()->user()->employee->id;
        } else {
            $employee_id = $request->input('employee_id');
        }

        if ($visitor) {
            $preArray['expected_date']  = $request->input('expected_date');
            $preArray['expected_time']  = date('H:i:s', strtotime($request->input('expected_time')));
            $preArray['comment']        = $request->input('comment');
            $preArray['visitor_id']     = $visitor->id;
            $preArray['employee_id']     = $employee_id;
            $result = PreRegister::create($preArray);


            try {
                $result->visitor->notify(new SendInvitationToVisitors($result));
            } catch (\Exception $e) {
                // Using a generic exception
            }
        }
        return $result;
    }

    /**
     * @param Request $request
     * @param $id
     * @return mixed
     */
    public function update(Request $request, $id)
    {
        $pre_register = PreRegister::findOrFail($id);

        $input['first_name']                 = $request->input('first_name');
        $input['last_name']                  = $request->input('last_name');
        $input['email']                      = $request->input('email');
        $input['phone']                      = preg_replace("/[^0-9]/", "", $request->input('phone'));
        $input['country_code']               = $request->input('country_code');
        $input['country_code_name']          = $request->input('country_code_name');
        $input['gender']                     = $request->input('gender');
        $input['address']                    = $request->input('address');
        $input['employee_id']                = $request->input('employee_id');
        $input['national_identification_no'] = $request->input('national_identification_no');
        $input['is_pre_register']            = true;
        $input['status']                     = Status::ACTIVE;
        $input['creator_id']                 = 1;
        $input['creator_type']               = 'App\Models\User';
        $input['editor_type']                = 'App\Models\User';
        $input['editor_id']                  = 1;

        $file_name = 'qrcode-' . preg_replace("/[^0-9]/", "", $request->input('phone')) . '.png';
        $input['barcode']                    = $file_name;
        $file = public_path('qrcode/' . $file_name);
        QRCode::size(300)->format('png')->generate(route('checkin.pre-visitor-details', preg_replace("/[^0-9]/", "", $request->input('phone'))), $file);

        $pre_register->visitor->update($input);
        if (auth()->user()->getrole->name == 'Employee') {
            $employee_id = auth()->user()->employee->id;
        } else {
            $employee_id = $request->input('employee_id');
        }
        if ($pre_register) {
            $preArray['expected_date']  = $request->input('expected_date');
            $preArray['expected_time']  = date('H:i:s', strtotime($request->input('expected_time')));
            $preArray['comment']        = $request->input('comment');
            $preArray['employee_id'] = $employee_id;
            $pre_register->update($preArray);
            try {
                $pre_register->visitor->notify(new SendInvitationToVisitors($pre_register));
            } catch (\Exception $e) {
                // Using a generic exception
            }
        }
        return $pre_register;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        try {
            $PreRegister = PreRegister::find($id);
            // $PreRegister->visitor->delete();
            $PreRegister->delete();
            return true;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
