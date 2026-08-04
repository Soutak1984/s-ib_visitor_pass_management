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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class VisitorService
{
    /**
     * Generate a unique visitor registration number (format: yymmdd + sequence).
     * Avoids duplicate key errors when last row uses a different date format
     * or multiple visitors register on the same day.
     */
    public function generateUniqueRegNo(): string
    {
        $prefix = date('ymd'); // e.g. 260729

        $last = VisitingDetails::where('reg_no', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(reg_no, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        $seq = 1;
        if ($last && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', (string) $last->reg_no, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        // Guarantee uniqueness even under race / mixed legacy formats
        do {
            $regNo = $prefix . $seq;
            $exists = VisitingDetails::where('reg_no', $regNo)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $regNo;
    }

    /**
     * Store visitor photo on public/uploads (works without storage symlink).
     * Supports file upload (image) and camera capture (captured_image base64).
     */
    protected function attachVisitorImage($visitingDetails, $request): void
    {
        try {
            if ($request->hasFile('image')) {
                $visitingDetails->clearMediaCollection('visitor');
                $visitingDetails->addMediaFromRequest('image')
                    ->toMediaCollection('visitor', 'public_uploads');
                return;
            }

            $captured = $request->input('captured_image');
            if (!blank($captured) && Str::startsWith($captured, 'data:image')) {
                $image = str_replace(['data:image/png;base64,', 'data:image/jpeg;base64,', ' '], ['', '', '+'], $captured);
                $imageName = 'visitor_' . Str::random(12) . '.png';
                $tempPath = storage_path('app/' . $imageName);
                File::put($tempPath, base64_decode($image));

                if (File::exists($tempPath)) {
                    $visitingDetails->clearMediaCollection('visitor');
                    $visitingDetails->addMedia($tempPath)
                        ->usingFileName($imageName)
                        ->toMediaCollection('visitor', 'public_uploads');
                    File::delete($tempPath);
                }
            }
        } catch (\Exception $e) {
            // Keep registration working even if photo fails
            \Log::warning('Visitor image attach failed: ' . $e->getMessage());
        }
    }

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
        $reg_no = $this->generateUniqueRegNo();

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
        generate_qrcode_png(route('checkin.visitor-details', preg_replace("/[^0-9]/", "", $request->input('phone'))), $file);
        $visitor = Visitor::create($input);

        if ($visitor) {
            $visiting['reg_no']             = $reg_no;
            $visiting['purpose']            = $request->input('purpose');
            $visiting['company_name']       = $request->input('company_name');
            $visiting['employee_id']        = $request->input('employee_id');
            $visiting['visitor_id']         = $visitor->id;
            $visiting['status']             = VisitorStatus::PENDDING;
            $visiting['user_id']            = $request->input('employee_id');
            $visiting['entry_gate_number']  = $request->input('entry_gate_number');
            $visiting['vehicle_number']     = $request->input('vehicle_number');
            $visiting['vehicle_compliance'] = $request->input('vehicle_compliance');
            $visiting['vehicle_remarks']    = $request->input('vehicle_remarks');
            $visiting['creator_id']         = 1;
            $visiting['creator_type']       = 'App\Models\User';
            $visiting['editor_type']        = 'App\Models\User';
            $visiting['editor_id']          = 1;
            $visitingDetails                = VisitingDetails::create($visiting);
            $this->attachVisitorImage($visitingDetails, $request);

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
    $visitingDetails = VisitingDetails::findOrFail($id);

    // Update VisitingDetails (including vehicle + entry gate fields)
    $visitingDetails->update([
        'purpose'            => $request->purpose,
        'company_name'       => $request->company_name,
        'employee_id'        => $request->employee_id,
        'entry_gate_number'  => $request->entry_gate_number,
        'vehicle_number'     => $request->vehicle_number,
        'vehicle_compliance' => $request->vehicle_compliance,
        'vehicle_remarks'    => $request->vehicle_remarks,
    ]);

    // Update Visitor model (basic info)
    if ($visitingDetails->visitor) {
        $visitingDetails->visitor->update([
            'first_name'                  => $request->first_name,
            'last_name'                   => $request->last_name,
            'email'                       => $request->email,
            'phone'                       => $request->phone,
            'country_code'                => $request->country_code,
            'country_code_name'           => $request->country_code_name,
            'gender'                      => $request->gender,
            'national_identification_no'  => $request->national_identification_no,
            'address'                     => $request->address,
        ]);
    }

    // Handle file upload or camera capture
    $this->attachVisitorImage($visitingDetails, $request);

    return $visitingDetails;
}
    public function makePrevious($request)
    {
        $reg_no = $this->generateUniqueRegNo();

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
        generate_qrcode_png(route('checkin.visitor-details', preg_replace("/[^0-9]/", "", $request['phone'])), $file);
        $visitor->save();
        if ($visitor) {
            $visiting['reg_no'] = $reg_no;
            $visiting['purpose'] = $request->input('purpose');
            $visiting['company_name'] = $request->input('company_name');
            $visiting['employee_id'] = $request->input('employee_id');
            $visiting['visitor_id'] = $visitor->id;
            $visiting['status'] = VisitorStatus::PENDDING;
            $visiting['user_id'] = $request->input('employee_id');
            $visiting['entry_gate_number'] = $request->input('entry_gate_number');
            $visiting['vehicle_number'] = $request->input('vehicle_number');
            $visiting['vehicle_compliance'] = $request->input('vehicle_compliance');
            $visiting['vehicle_remarks'] = $request->input('vehicle_remarks');
            $visiting['creator_id'] = 1;
            $visiting['creator_type'] = 'App\Models\User';
            $visiting['editor_type'] = 'App\Models\User';
            $visiting['editor_id'] = 1;
            $visitingDetails = VisitingDetails::create($visiting);
            $this->attachVisitorImage($visitingDetails, $request);


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
