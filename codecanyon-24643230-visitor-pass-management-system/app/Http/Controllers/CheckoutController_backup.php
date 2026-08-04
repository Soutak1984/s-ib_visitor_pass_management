<?php

namespace App\Http\Controllers;

use App\Models\VisitingDetails;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{

    function __construct()
    {
    }

    public function index(Request $request)
    {
        return view('frontend.checkout.index', ['details' => true]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getVisitor(Request $request)
    {
        $request->validate(
            ['visitorID' => 'required|numeric',],
            ['visitorID.required' => 'Visitor ID required', 'visitorID.numeric' => 'ID must be numeric']
        );

        $visitingDetails = VisitingDetails::where('reg_no', $request->visitorID)->first();
        $details = false;
        if($visitingDetails){
            return view('frontend.checkout.information', compact('visitingDetails', 'details'));
        }
        return redirect()->back()->with('error', 'Visitor information not found.');
    }

    public function update(VisitingDetails $visitingDetails)
    {
        $visitingDetails->checkout_at = date('y-m-d H:i');
        $visitingDetails->save();
        return redirect()->route('/')->with('success', 'Successfully Check-Out');
    }
}
