<?php

namespace App\Http\Requests\Api;

use App\Models\PreRegister;
use App\Models\Visitor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public $id;
    public  function __construct($id = null)
    {
        $this->id = $id ? $id : 0;
    }
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->id) {
            $preRegister = PreRegister::find($this->id);
            $email    = ['required', 'email', 'string', Rule::unique("visitors", "email")->ignore($preRegister->visitor_id)];
            $phone    = ['required', 'string', Rule::unique("visitors", "phone")->ignore($preRegister->visitor_id)];
            $national_identification_no    = ['required', 'string', Rule::unique("visitors", "national_identification_no")->ignore($preRegister->visitor_id)];
        } else {
            $email    = ['required', 'email', 'string', 'unique:visitors,email'];
            $phone    = ['required', 'string', 'unique:visitors,phone'];
            $national_identification_no    = ['required', 'string', 'max:100', 'unique:visitors,national_identification_no'];
        }
        $employee_id = ['required', 'numeric'];
        if (auth()->user()->getrole->name == 'Employee') {
            $employee_id = [];
        }
        return  [
            'first_name'                => 'required|string|max:100',
            'last_name'                 => 'required|string|max:100',
            'email'                     => $email,
            'phone'                     => $phone,
            'national_identification_no' => $national_identification_no,
            'employee_id'               => $employee_id,
            'gender'                    => 'required|numeric',
            'expected_date'             => 'required',
            'expected_time'             => 'required',
            'comment'                   => 'nullable|max:191',
            'address'                   => 'nullable|max:191',
        ];
    }
}
