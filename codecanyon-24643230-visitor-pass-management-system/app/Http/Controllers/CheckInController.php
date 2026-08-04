<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Enums\Status;
use App\Models\Visitor;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\PreRegister;
use Illuminate\Support\Str;
use App\Enums\VisitorStatus;
use Illuminate\Http\Request;
use App\Models\VisitingDetails;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Http\Services\JwtTokenService;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EmployeConfirmation;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Http\Services\PushNotificationService;
use Illuminate\Support\Facades\Log;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class CheckInController extends Controller
{
    public $disable;

    function __construct() {}

    public function index()
    {
        session()->forget('visitor');
        session()->forget('is_returned');
        return view('frontend.check-in.home-page');
    }

    public function scanQr()
    {
        return view('frontend.check-in.cameraPreview');
    }

    public function createStepOne(Request $request)
    {
        $employees = Employee::where('status', Status::ACTIVE)->get();
        $visitor = (object)$request->session()->get('visitor');
        $employee_id = "";
        $purpose = "";
        $company_name = "";
        $disable = false;

        if (!blank($visitor) && isset($visitor->id)) {
            if (session()->has('pre-register')) {
                $visitingDetails = PreRegister::where('visitor_id', $visitor->id)->latest()->first();
                if (!blank($visitingDetails)) {
                    $employee_id = $visitingDetails->employee_id;
                }
            } else {
                $visitingDetails = VisitingDetails::where('visitor_id', $visitor->id)->latest()->first();
                if (!blank($visitingDetails)) {
                    $company_name = $visitingDetails->company_name;
                    $employee_id = $visitingDetails->employee_id;
                    $purpose = $visitingDetails->purpose;
                }
            }
        }

        if (!blank($visitor) && isset($visitor->disable)) {
            $disable = $visitor->disable;
        }

        return view('frontend.check-in.step-one', compact('employees', 'visitor', 'company_name', 'disable', 'employee_id', 'purpose'));
    }

public function postCreateStepOne(Request $request)
{
    if ($request->session()->get('is_returned') == false || empty($request->session()->get('is_returned'))) {
        // ==================== NEW VISITOR ====================
        $emailValidation = '';
        if (!blank($request->get('email'))) {
            $emailValidation = $request->validate(['email' => 'unique:visitors,email']);
        }

        if (setting('terms_visibility_status')) {
            $validatedData = $request->validate([
                'first_name'                 => 'required',
                'last_name'                  => 'required',
                'phone'                      => 'required|unique:visitors,phone',
                'country_code'               => '',
                'country_code_name'          => '',
                'purpose'                    => 'required',
                'employee_id'                => 'required|numeric',
                'gender'                     => 'required|numeric',
                'company_name'               => '',
                'company_employee_id'        => '',
                'national_identification_no' => 'required|unique:visitors,national_identification_no',
                'vehicle_number'             => 'nullable|string|max:50',
                'vehicle_compliance'         => 'nullable|in:Ok,Not Ok',
                'vehicle_remarks'            => 'nullable|string',
                'entry_gate_number'          => 'required|string|max:50',
                'is_group_enabled'           => '',
                'address'                    => '',
                'oldVisitor'                 => '',
                'accept_tc'                  => 'accepted',
            ]);
        } else {
            $validatedData = $request->validate([
                'first_name'                 => 'required',
                'last_name'                  => 'required',
                'phone'                      => 'required|unique:visitors,phone',
                'country_code'               => '',
                'country_code_name'          => '',
                'purpose'                    => 'required',
                'employee_id'                => 'required|numeric',
                'gender'                     => 'required|numeric',
                'company_name'               => '',
                'company_employee_id'        => '',
                'national_identification_no' => 'required|unique:visitors,national_identification_no',
                'vehicle_number'             => 'nullable|string|max:50',
                'vehicle_compliance'         => 'nullable|in:Ok,Not Ok',
                'vehicle_remarks'            => 'nullable|string',
                'entry_gate_number'          => 'required|string|max:50',
                'is_group_enabled'           => '',
                'address'                    => '',
                'oldVisitor'                 => '',
            ]);
        }

        if (!blank($emailValidation)) {
            $validatedData = array_merge($validatedData, $emailValidation);
        }
    } else {
        // ==================== RETURNING VISITOR ====================
        $visitor = Visitor::where('email', $request->get('email') ?? '')
            ->orWhere('phone', $request->get('phone') ?? '')
            ->orWhere('national_identification_no', $request->get('national_identification_no') ?? '')
            ->first();

        $email = $visitor 
            ? ['email', 'string', 'unique:visitors,email,' . $visitor->id] 
            : ['email', 'string', 'unique:visitors,email'];

        $phone = $visitor 
            ? ['required', 'string', Rule::unique("visitors", "phone")->ignore($visitor)] 
            : ['required', 'string', 'unique:visitors,phone'];

        $national = $visitor 
            ? ['required', 'string', 'unique:visitors,national_identification_no,' . $visitor->id] 
            : ['required', 'string', 'unique:visitors,national_identification_no'];

        if (setting('terms_visibility_status')) {
            $validatedData = $request->validate([
                'first_name'                 => 'required',
                'last_name'                  => 'required',
                'email'                      => $email,
                'phone'                      => $phone,
                'country_code'               => '',
                'country_code_name'          => '',
                'purpose'                    => 'required',
                'employee_id'                => 'required|numeric',
                'gender'                     => 'required|numeric',
                'company_name'               => '',
                'company_employee_id'        => '',
                'national_identification_no' => $national,
                'vehicle_number'             => 'nullable|string|max:50',
                'vehicle_compliance'         => 'nullable|in:Ok,Not Ok',
                'vehicle_remarks'            => 'nullable|string',
                'entry_gate_number'          => 'required|string|max:50',
                'is_group_enabled'           => '',
                'address'                    => '',
                'oldVisitor'                 => '',
                'accept_tc'                  => 'accepted',
            ]);
        } else {
            $validatedData = $request->validate([
                'first_name'                 => 'required',
                'last_name'                  => 'required',
                'email'                      => $email,
                'phone'                      => $phone,
                'country_code'               => '',
                'country_code_name'          => '',
                'purpose'                    => 'required',
                'employee_id'                => 'required|numeric',
                'gender'                     => 'required|numeric',
                'company_name'               => '',
                'company_employee_id'        => '',
                'national_identification_no' => $national,
                'vehicle_number'             => 'nullable|string|max:50',
                'vehicle_compliance'         => 'nullable|in:Ok,Not Ok',
                'vehicle_remarks'            => 'nullable|string',
                'entry_gate_number'          => 'required|string|max:50',
                'is_group_enabled'           => '',
                'address'                    => '',
                'oldVisitor'                 => '',
            ]);
        }
    }

    $request->session()->put('visitor', $validatedData);
    return redirect()->route('check-in.step-two');
}

    public function createStepTwo(Request $request)
    {
        $visitingDetails = $request->session()->get('visitor');
        $employee = Employee::find($visitingDetails['employee_id'] ?? null);
        $visitor = Visitor::where('phone', $visitingDetails['phone'] ?? '')->first();

        $image = $visitor 
            ? (VisitingDetails::where('visitor_id', $visitor->id)->first()->images ?? (setting('photo_capture_enable') ? 'default/user.png' : ''))
            : (setting('photo_capture_enable') ? 'default/user.png' : '');

        return view('frontend.check-in.step-two', compact('employee', 'visitingDetails', 'image'));
    }

public function store(Request $request)
{
    $getVisitor = $request->session()->get('visitor');
    if (!$getVisitor) {
        return redirect()->route('check-in.step-one')->with('error', 'Visitor information not found!');
    }

    // ==============================
    // PHOTO HANDLING (FOR BOTH NEW & RETURNING)
    // ==============================
    $imageName = null;
    if ($request->has('photo') && setting('photo_capture_enable')) {
        $encoded_data = $request['photo'];
        $image = str_replace(['data:image/png;base64,', ' '], ['', '+'], $encoded_data);
        $imageName = Str::random(10) . '.png';
        file_put_contents($imageName, base64_decode($image));

        if (File::exists($imageName)) {
            try {
                OptimizerChainFactory::create()->optimize($imageName);
            } catch (\Exception $e) {
                Log::warning('Image optimization failed: ' . $e->getMessage());
            }
        }
    }

    // ==============================
    // GENERATE REG NO (unique, safe)
    // ==============================
    $reg_no = app(\App\Http\Services\Visitor\VisitorService::class)->generateUniqueRegNo();

    $phoneClean = preg_replace("/[^0-9]/", "", $getVisitor['phone'] ?? '');

    // ==============================
    // NEW VISITOR
    // ==============================
    if ($request->session()->get('is_returned') == false || empty($request->session()->get('is_returned'))) {
        $input = [
            'first_name' => $getVisitor['first_name'] ?? '',
            'last_name' => $getVisitor['last_name'] ?? '',
            'email' => $getVisitor['email'] ?? '',
            'phone' => $phoneClean,
            'country_code' => $getVisitor['country_code'] ?? '',
            'country_code_name' => $getVisitor['country_code_name'] ?? '',
            'gender' => $getVisitor['gender'] ?? '',
            'address' => $getVisitor['address'] ?? '',
            'national_identification_no' => $getVisitor['national_identification_no'] ?? '',
            'is_pre_register' => false,
            'status' => Status::ACTIVE,
            'creator_id' => 1,
            'creator_type' => 'App\Models\User',
            'editor_type' => 'App\Models\User',
            'editor_id' => 1,
            'barcode' => 'qrcode-' . $phoneClean . '.png',
        ];

        $file = public_path('qrcode/qrcode-' . $phoneClean . '.png');
        generate_qrcode_png(route('checkin.visitor-details', $phoneClean), $file);
        $visitor = Visitor::create($input);
    } 
    // ==============================
    // RETURNING VISITOR
    // ==============================
    else {
        $visitor = Visitor::where('is_pre_register', false)
            ->where(function ($q) use ($phoneClean, $getVisitor) {
                $q->where('phone', 'like', '%' . $phoneClean . '%')
                  ->orWhere('phone', $getVisitor['phone'] ?? '');
            })
            ->first();

        if (!$visitor) {
            return redirect()->route('check-in.step-one')
                ->with('error', 'Re-visitor not found. Please check phone number or register as new.');
        }

        $visitor->first_name = $getVisitor['first_name'] ?? $visitor->first_name ?? '';
        $visitor->last_name = $getVisitor['last_name'] ?? $visitor->last_name ?? '';
        $visitor->email = $getVisitor['email'] ?? $visitor->email ?? '';
        $visitor->national_identification_no = $getVisitor['national_identification_no'] ?? $visitor->national_identification_no ?? '';
        $visitor->gender = $getVisitor['gender'] ?? $visitor->gender ?? '';
        $visitor->address = $getVisitor['address'] ?? $visitor->address ?? '';
        $visitor->is_pre_register = false;
        $visitor->barcode = 'qrcode-' . $phoneClean . '.png';

        $file = public_path('qrcode/qrcode-' . $phoneClean . '.png');
        generate_qrcode_png(route('checkin.visitor-details', $phoneClean), $file);
        $visitor->save();
    }

    // ==============================
    // CREATE VISITING DETAILS + NEW VEHICLE FIELDS
    // ==============================
    $visitingDetails = VisitingDetails::create([
        'reg_no'                => $reg_no,
        'purpose'               => $getVisitor['purpose'] ?? '',
        'company_name'          => $getVisitor['company_name'] ?? '',
        'employee_id'           => $getVisitor['employee_id'] ?? null,
        'visitor_id'            => $visitor->id,
        'status'                => VisitorStatus::PENDDING,
        'user_id'               => $getVisitor['employee_id'] ?? null,
        'creator_id'            => 1,
        'creator_type'          => 'App\Models\User',
        'editor_type'           => 'App\Models\User',
        'editor_id'             => 1,

        // ==================== NEW VEHICLE + GATE FIELDS ====================
        'vehicle_number'        => $getVisitor['vehicle_number'] ?? null,
        'vehicle_compliance'    => $getVisitor['vehicle_compliance'] ?? null,
        'vehicle_remarks'       => $getVisitor['vehicle_remarks'] ?? null,
        'entry_gate_number'     => $getVisitor['entry_gate_number'] ?? null,
    ]);

    // ==============================
    // ATTACH PHOTO
    // ==============================
    if ($imageName && File::exists($imageName)) {
        try {
            $visitingDetails->addMedia($imageName)
                ->toMediaCollection('visitor', 'public_uploads');
        } catch (\Exception $e) {
            Log::warning('Media attach failed: ' . $e->getMessage());
        }
        File::delete($imageName);
    }

    // ==============================
    // NOTIFICATIONS
    // ==============================
    try {
        $token = app(JwtTokenService::class)->jwtToken($visitingDetails);
        $visitingDetails->employee->user->notify(new EmployeConfirmation($visitingDetails, $token));
    } catch (\Exception $e) {
        Log::info('Notification error: ' . $e->getMessage());
    }

    try {
        app(PushNotificationService::class)->sendWebNotification($visitingDetails);
    } catch (\Exception $e) {
        Log::info('Push notification error: ' . $e->getMessage());
    }

    return redirect()->route('check-in.show', $visitingDetails->id);
}

    public function show(Request $request, $id)
    {
        $visitingDetails = VisitingDetails::find($id);
        if ($visitingDetails) {
            $visitorDetail = VisitingDetails::where('visitor_id', $visitingDetails->visitor_id)->first();
            $visitingDetails['photo'] = $visitorDetail->images ?? '';
            return view('frontend.check-in.show', compact('visitingDetails'));
        }
        session()->forget('visitor');
        session()->forget('is_returned');
        return redirect('/check-in');
    }

    public function visitor_return()
    {
        return view('frontend.check-in.return');
    }

    public function find_visitor(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required']);

        if ($validator->fails()) {
            return redirect()->route('check-in.return')->withErrors($validator)->withInput();
        }

        $search = trim($request->email);
        $phoneClean = preg_replace('/[^0-9]/', '', $search);

        $visitor = Visitor::where('is_pre_register', false)
            ->where(function ($query) use ($search, $phoneClean) {
                $query->where('phone', 'like', '%' . $phoneClean . '%')
                      ->orWhere('email', $search)
                      ->orWhere('national_identification_no', $search);
            })
            ->first();

        if (!$visitor) {
            return redirect()->route('check-in.return')
                ->with('error', 'Visitor not found. Please check phone number or register as new visitor.');
        }

        $blocked = VisitingDetails::where('visitor_id', $visitor->id)->where('disable', true)->latest()->first();
        if ($blocked) {
            return redirect()->route('home')->with('error', 'This visitor has been Blocked!');
        }

        $visitorDetail = VisitingDetails::where('visitor_id', $visitor->id)->first();
        if ($visitorDetail) $visitor->image = $visitorDetail->images;

        $request->session()->put('visitor', $visitor);
        $visitor->disable = (Auth::id() == 1) ? false : true;
        $request->session()->put('is_returned', true);

        return redirect()->route('check-in.step-one');
    }

    public function checkVisitor($boolean, $request)
    {
        return Visitor::where('is_pre_register', $boolean)
            ->where(function ($q) use ($request) {
                $q->orWhere('email', $request->email)
                  ->orWhere('phone', $request->email)
                  ->orWhere('national_identification_no', $request->email);
            })->exists();
    }

    public function checkPreRegister($boolean, $request)
    {
        return Visitor::where('is_pre_register', $boolean)
            ->where(function ($q) use ($request) {
                $q->orWhere('email', $request->email)
                  ->orWhere('phone', $request->email)
                  ->orWhere('national_identification_no', $request->email);
            })->exists();
    }

    public function find_pre_visitor(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required']);

        $validator->after(function ($validator) use ($request) {
            if (!$this->checkPreRegister(true, $request)) {
                $validator->errors()->add('email', 'Pre-Register not found!');
            }
        });

        if ($validator->fails()) {
            return redirect()->route('check-in.pre.registered')->withErrors($validator)->withInput();
        }

        $visitor = Visitor::where('is_pre_register', true)
            ->where(function ($query) use ($request) {
                $query->orWhere('email', $request->email)
                      ->orWhere('phone', $request->email)
                      ->orWhere('national_identification_no', $request->email);
            })->first();

        if ($visitor) {
            $today = Carbon::now()->toDateString();
            $visitDetails = PreRegister::where('visitor_id', $visitor->id)
                ->where('expected_date', '<=', $today)->first();

            if (!$visitDetails) {
                return redirect()->back()->with('error', 'Sorry, Your Appointment date has not arrived yet!');
            }

            $preData = PreRegister::where('visitor_id', $visitor->id)->first();
            $visitor->employee_id = $preData->employee_id ?? null;

            $visitorDetail = VisitingDetails::where('visitor_id', $visitor->id)->first();
            if ($visitorDetail) $visitor->image = $visitorDetail->images;

            $visitor->disable = (Auth::id() == 1) ? false : true;

            $request->session()->put('visitor', $visitor);
            $request->session()->put('pre-register', true);
            $request->session()->put('is_returned', true);

            return redirect()->route('check-in.step-one');
        }

        return redirect()->route('check-in.pre.registered');
    }

    public function pre_registered()
    {
        return view('frontend.check-in.pre_registered');
    }

    public function visitorDetails($visitorPhone)
    {
        $visitor = Visitor::where('phone', $visitorPhone)->first();
        if (!$visitor) {
            $employee = Employee::where('phone', $visitorPhone)->first();
            if ($employee) {
                $checkout = Attendance::where(['user_id' => $employee->id, 'date' => date('Y-m-d')])->first();
                if (!$checkout) {
                    $checkout = new Attendance(['title' => 'Office', 'checkin_time' => date('g:i A'), 'date' => date('Y-m-d'), 'user_id' => $employee->id]);
                    $checkout->save();
                    return redirect()->route('home')->withSuccess("Check-in Successful!");
                } else {
                    $checkout->checkout_time = date('g:i A');
                    $checkout->save();
                    return redirect()->route('home')->withSuccess("Check-out Successful!");
                }
            }
            return redirect()->route('home')->withWarning('No record found!');
        }

        $blocked = VisitingDetails::where('visitor_id', $visitor->id)->where('disable', true)->latest()->first();
        if ($blocked) {
            return redirect()->route('home')->with('error', 'This visitor has been Blocked!');
        }

        $visitorDetail = VisitingDetails::where('visitor_id', $visitor->id)->first();
        if ($visitorDetail) $visitor->image = $visitorDetail->images;

        $request->session()->put('visitor', $visitor);
        $visitor->disable = (Auth::id() == 1) ? false : true;
        $request->session()->put('is_returned', true);

        return redirect()->route('check-in.step-one');
    }
}