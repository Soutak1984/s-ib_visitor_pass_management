<?php

namespace App\Http\Controllers\Api\v1\Auth;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use App\Http\Resources\v1\MeResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Api\ProfileUpdateRequest;
use App\Http\Requests\Api\PasswordUpdateRequest;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class MeController extends Controller
{
    use ApiResponse;
    public function __construct()
    {
        // $this->middleware(['auth:api']);
    }

    public function action(Request $request)
    {
        try {
            $data = new MeResource($request->user());
        } catch (\Exception $e) {
            return $this->errorResponse(['status' => 500, 'message' => $e->getMessage(), 'profile' => (object)[]]);
        }
        return $this->successResponse(['status' => 200, 'message' => "success", 'profile' => $data]);
    }

    public function refresh()
    {
        $token = JWTAuth::getToken();
        if (!$token) {
            return $this->successResponse([
                'status'  => 401,
                'message' => 'Token not provided',
            ], 401);
        }

        try {
            $token = JWTAuth::refresh($token);
        } catch (TokenInvalidException $e) {
            return $this->errorResponse([
                'status'  => 401,
                'message' => $e->getMessage(),
            ], 401);
        }

        return $this->successResponse([
            'success'    => true,
            'token'      => $token,
            "token_type" => "bearer",
            'expires_in' => config('jwt.ttl') * 360000,
        ], 200);
    }

    public function update(Request $request)
    {
        $profile = auth()->user();

        if (blank($profile)) {
            return $this->errorResponse([
                'status'  => 401,
                'message' => 'You try to using invalid username or password',
            ], 401);
        }

        $validator = new ProfileUpdateRequest($profile->id);
        $validator = Validator::make($request->all(), $validator->rules());
        if ($validator->fails()) {
            return $this->errorResponse([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }

        $profile->first_name        = $request->get('first_name');
        $profile->last_name         = $request->get('last_name');
        $profile->email             = $request->get('email');
        $profile->phone             = $request->get('phone');
        $profile->country_code      = $request->get('country_code');
        $profile->country_code_name = $request->get('country_code_name');
        $profile->address           = $request->get('address');
        if ($request->username) {
            $profile->username = $request->username;
        }
        $profile->save();

        if (auth()->user()->myrole == UserRole::EMPLOYEE) {
            $employee = Employee::where('user_id', auth()->user()->id)->first();
            $employee->first_name        = $request->get('first_name');
            $employee->last_name         = $request->get('last_name');
            $employee->phone             = $request->get('phone');
            $employee->country_code      = $request->get('country_code');
            $employee->country_code_name = $request->get('country_code_name');
            $employee->save();
        }

        if (request()->file('image')) {
            $profile->media()->delete();
            $profile->addMedia(request()->file('image'))->toMediaCollection('user');
        }

        return $this->successResponse([
            'status'  => 200,
            'message' => 'Successfully Updated Profile',
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = new PasswordUpdateRequest();
        $validator = Validator::make($request->all(), $validator->rules());

        if ($validator->fails()) {
            return $this->errorResponse([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }

        $profile           = auth()->user();
        $profile->password = bcrypt($request->get('password'));
        $profile->save();
        return $this->successResponse([
            'status'  => 200,
            'message' => 'Successfully Updated Password',
        ], 200);
    }

    public function device(Request $request)
    {
        $validator = Validator::make($request->all(), ['device_token' => 'required']);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => $validator->errors(),
            ], 422);
        }

        $user               = auth()->user();
        $user->device_token = $request->device_token;
        $user->save();

        return response()->json([
            'status'  => 200,
            'message' => 'Successfully device updated',
        ], 200);
    }

    private function splitName($name)
    {
        $name       = trim($name);
        $last_name  = (strpos($name, ' ') === false) ? '' : preg_replace('#.*\s([\w-]*)$#', '$1', $name);
        $first_name = trim(preg_replace('#' . $last_name . '#', '', $name));
        return [$first_name, $last_name];
    }
}
