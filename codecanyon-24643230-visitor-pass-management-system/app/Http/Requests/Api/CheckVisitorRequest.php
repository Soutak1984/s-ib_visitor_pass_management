<?php

namespace App\Http\Requests\Api;

use App\Models\Visitor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckVisitorRequest extends FormRequest
{

    private $visitor_id;
    public  function __construct($id = null)
    {
        parent::__construct();
        $this->visitor_id = $id ? $id : 0;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->visitor) {
            $email                      = blank(request('email')) ? '' : ['string', Rule::unique("visitors", "email")->ignore($this->visitor->visitor_id)];
            $national_identification_no = ['required', 'string', 'max:100', Rule::unique("visitors", "national_identification_no")->ignore($this->visitor->visitor_id)];
            $phone                      = ['required', 'string', Rule::unique("visitors", "phone")->ignore($this->visitor->visitor_id)];
        } elseif ($this->visitor_id) {
            $email    = blank(request('email')) ? '' : ['email', 'string'];
            $phone    = ['required', 'string'];
            $national_identification_no    = ['required', 'string', 'max:100'];
        } else {
            $uniqueEmail = $this->checkUniqueEmail(request('email'), request('visitor_old'));
            $uniquePhone = $this->checkUniquePhone(request('phone'), request('visitor_old'));
            $uniqueNID = $this->checkUniqueNID(request('national_identification_no'), request('visitor_old'));
            $email    = blank(request('email')) ? '' : $uniqueEmail;
            $phone    = $uniquePhone;
            $national_identification_no    = $uniqueNID;
        }

        return [
            'first_name'                => 'required|string|max:100',
            'last_name'                 => 'required|string|max:100',
            'email'                     => $email,
            'phone'                     => $phone,
            'employee_id'               => 'required|numeric',
            'gender'                    => 'required|numeric',
            'company_name'              => 'nullable|max:100',
            'national_identification_no' => $national_identification_no,
            'purpose'                   => 'required|max:191',
            'address'                   => 'nullable|max:191',
        ];
    }

    public function checkUniqueEmail($email, $visitor_old)
    {
        $visitor = Visitor::where('email', $email)->first();
        if ($visitor && ($visitor_old == 1)) {
            return  ['email', 'string'];
        } else {
            return  ['email', 'string', 'unique:visitors,email'];
        }
    }
    public function checkUniquePhone($phone, $visitor_old)
    {
        $visitor = Visitor::where('phone', $phone)->first();
        if ($visitor && ($visitor_old == 1)) {
            return  ['required', 'string', 'numeric', 'regex:/^[0-9]/'];
        } else {
            return  ['required', 'string', 'numeric', 'regex:/^[0-9]/', 'unique:visitors,phone'];
        }
    }
    public function checkUniqueNID($nid, $visitor_old)
    {
        $visitor = Visitor::where('national_identification_no', $nid)->first();
        if ($visitor && ($visitor_old == 1)) {
            return  ['required', 'string', 'max:100'];
        } else {
            return  ['required', 'string', 'max:100', 'unique:visitors,national_identification_no'];
        }
    }
}
