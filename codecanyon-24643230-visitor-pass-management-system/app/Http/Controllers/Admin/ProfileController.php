<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\ProfileRequest;
use App\Http\Controllers\BackendController;
use App\Http\Requests\ChangePasswordRequest;
use App\Models\Employee;

class ProfileController extends BackendController
{

    public function index()
    {
        $this->data['user'] = auth()->user();
        return view('admin.profile.index', $this->data);
    }

    public function update(ProfileRequest $request)
    {

        $user                    = auth()->user();
        $user->first_name        = $request->get('first_name');
        $user->last_name         = $request->get('last_name');
        $user->email             = $request->get('email');
        $user->phone             = $request->get('phone');
        $user->country_code      = $request->get('country_code');
        $user->country_code_name = $request->get('country_code_name');
        $user->username          = $request->username ?? $this->username($request->email);
        $user->address           = $request->get('address');
        $user->save();

        if (auth()->user()->myrole == UserRole::EMPLOYEE) {
            $employee = Employee::where('user_id', auth()->user()->id)->first();
            $employee->first_name        = $request->get('first_name');
            $employee->last_name         = $request->get('last_name');
            $employee->phone             = $request->get('phone');
            $employee->country_code      = $request->get('country_code');
            $employee->country_code_name = $request->get('country_code_name');
            $employee->save();
        }

        attachMediaFromRequest($user, $request, 'user');

        return redirect(route('admin.profile'))->withSuccess('The Data Updated Successfully');
    }

    public function change(ChangePasswordRequest $request)
    {
        $user           = auth()->user();
        $user->password = Hash::make(request('password'));
        $user->save();
        return redirect(route('admin.profile'))->withSuccess('The Password updated successfully');
    }

    private function username($email)
    {
        $emails = explode('@', $email);
        return $emails[0] . mt_rand();
    }
}
