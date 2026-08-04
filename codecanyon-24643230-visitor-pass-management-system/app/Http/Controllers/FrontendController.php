<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Services\JwtTokenService;
use App\Models\Visitor;
use App\Models\User;

class FrontendController extends Controller
{
    public $data = [];
    protected $jwtTokenService;

    public function __construct(JwtTokenService $jwtTokenService)
    {
        $this->data['site-title'] = 'Frontend';
        $this->jwtTokenService = $jwtTokenService;
    }
    public function changeStatus($status, $token)
    {
        try {
            $data =  $this->jwtTokenService->jwtTokenDecode($token);
            if (auth()->user()) {
                $this->jwtTokenService->changeStatus($data->visitorID, $status);
                return redirect()->route('admin.dashboard.index')->withSuccess('The Status Change successfully!');
            } else {
                $result = User::findorFail($data->employee_user_id);
                if ($result) {
                    Auth::login($result);
                    $this->jwtTokenService->changeStatus($data->visitorID, $status);
                    return redirect()->route('admin.dashboard.index')->withSuccess('The Status Change successfully!');
                } else {
                    return redirect()->route('/')->withError('These credentials do not match our records');
                }
            }
        } catch (\Exception $e) {
            //
        }
    }

    public function qrcode($number)
    {
        $visitor = Visitor::select('barcode')->where('phone', $number)->first();

        return view('frontend.check-in.qrcode', compact('visitor'));
    }

    public function termsConditions()
    {
        if (setting('terms_visibility_status')) {
            return view('frontend.termscondition');
        }

        return redirect()->route('/');
    }
}
