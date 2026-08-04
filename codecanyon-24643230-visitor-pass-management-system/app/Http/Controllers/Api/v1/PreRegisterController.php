<?php

namespace App\Http\Controllers\Api\v1;


use App\Http\Resources\v1\PreRegisterVisitorResources;
use Carbon\Carbon;
use App\Models\Visitor;
use App\Models\PreRegister;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Models\VisitingDetails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\v1\VisitorResource;
use App\Http\Requests\Api\PreRegisterRequest;
use App\Http\Resources\v1\PreRegisterResources;
use App\Http\Services\PreRegister\PreRegisterService;


class PreRegisterController extends Controller
{
    use ApiResponse;

    protected $preRegisterService;


    public function __construct(PreRegisterService $preRegisterService)
    {
        $this->preRegisterService = $preRegisterService;

        $this->data['sitetitle'] = 'Pre-registers';
        $this->middleware(['auth:api'])->except('checkPreRegister');
        $this->middleware(['permission:pre-registers_create'])->only('create', 'store');
        $this->middleware(['permission:pre-registers_edit'])->only('edit', 'update');
        $this->middleware(['permission:pre-registers_delete'])->only('destroy');
        $this->middleware(['permission:pre-registers_show'])->only('show');
    }
    public function index()
    {
        try {
            $preregisters = PreRegisterResources::collection($this->preRegisterService->all());
            return $this->successResponse(['status' => 200, 'message' => 'success', 'preregisters' => $preregisters]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 401,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
        }
    }

    public function store(Request $request)
    {

        $validator = new PreRegisterRequest();
        $validator = Validator::make($request->all(), $validator->rules());
        if (!$validator->fails()) {
            try {
                DB::beginTransaction();
                $this->preRegisterService->make($request);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]);
            }
            return $this->successResponse(['status'=>200,'message' => 'Pre-Register saved']);
        } else {
            return response()->json([
                'status'  => 422,
                'message' => 'error',
                'error' => $validator->errors(),
            ], 422);
        }
    }

    public function show($id)
    {
        $preregister = $this->preRegisterService->find($id);

        if (blank($preregister)) {
            return $this->errorResponse(['status' => 404, 'message' => 'Pre-register Not Found', 'visitor' => (object)[]]);
        }
        try {
            $preregister = new PreRegisterResources($preregister);
            return $this->successResponse(['status' => 200, 'message' => 'success', 'visitor' => $preregister]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 402,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);
        }
    }

    public function update(Request $request, $id)
    {

        $validator = new PreRegisterRequest($id);
        $validator = Validator::make($request->all(), $validator->rules());
        if (!$validator->fails()) {
            try {
                DB::beginTransaction();
                $this->preRegisterService->update($request, $id);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return $this->errorResponse([
                    'status' => 402,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]);
            }
            return $this->successResponse(['status' => 200, 'message' => 'Pre-Register Updated']);
        } else {
            return $this->errorResponse([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }
    }

    public function destroy($id)
    {
        $preregister = PreRegister::where('id', $id)->first();
        if (!blank($preregister)) {
            try {
                $this->preRegisterService->delete($id);
            } catch (\Exception $e) {
                return response()->json([
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTrace(),
                ]);
            }
            return $this->successResponse('pre-register deleted');
        }
        return $this->errorResponse(['status' => 404, 'message' => 'You don\'t created Pre-register'], 404);
    }


    public function checkPreRegister(Request $request)
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
        $today = Carbon::now()->toDateString();
        $visitor = Visitor::with('preregister')->where([['is_pre_register', true], ['email', request()->email]])
            ->orWhere([['is_pre_register', true], ['phone', request()->email]])
            ->orWhere([['is_pre_register', true], ['national_identification_no', request()->email]])
            ->first();

        if (!empty($visitor)) {
            if (($visitor->preregister->expected_date <= $today)) {
                $visitor = new PreRegisterVisitorResources($visitor);
                return $this->successResponse(['status' => 200, 'message' => 'success', 'visitor' => $visitor]);
            } else {
                return $this->errorResponse(['status' => 404, 'message' => "Your Appointment date hasn't arrived yet.", 'visitor' => (object)[]]);
            }
        } else {
            return $this->errorResponse(['status' => 404, 'message' => "Pre register not found.", 'visitor' => (object)[]]);
        }
    }

    public function search($keyWord)
    {
        if (auth()->user()->getrole->name == 'Employee') {
            $preregisters = PreRegister::with('visitor')
                ->whereHas('visitor', function ($query) use ($keyWord) {
                    $query->where('phone', 'like', '%' . $keyWord . '%');
                    $query->orWhere('email', 'like', '%' . $keyWord . '%');
                    $query->orWhere('national_identification_no', 'like', '%' . $keyWord . '%');
                    $query->orWhere('first_name', 'like', '%' . $keyWord . '%');
                    $query->orWhere('last_name', 'like', '%' . $keyWord . '%');
                })->where('employee_id', auth()->user()->employee->id)

                ->orderBy('id', 'desc')->get();
        } else {
            $preregisters = PreRegister::with('visitor')
                ->whereHas('visitor', function ($query) use ($keyWord) {
                    $query->where('phone', 'like', '%' . $keyWord . '%');
                    $query->orWhere('email', 'like', '%' . $keyWord . '%');
                    $query->orWhere('national_identification_no', 'like', '%' . $keyWord . '%');
                })->orderBy('id', 'desc')->get();
        }

        if (!blank($preregisters)) {
            return $this->successResponse(['status' => 200, 'message' => 'success', 'preregisters' => PreRegisterResources::collection($preregisters)]);
        } else {
            return $this->errorResponse(['status' => 401, 'message' => "No Visiting Details Found", 'preRegisters' => []]);
        }
    }
}
