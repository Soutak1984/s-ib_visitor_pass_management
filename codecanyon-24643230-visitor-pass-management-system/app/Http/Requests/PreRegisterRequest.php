<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
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
        if ($this->pre_register) {
            $email    = ['nullable','email', 'string', Rule::unique("visitors", "email")->ignore($this->pre_register->visitor_id)];
            $phone    = ['required', 'string', Rule::unique("visitors", "phone")->ignore($this->pre_register->visitor_id)];
            $national_identification_no    = ['required','string', Rule::unique("visitors", "national_identification_no")->ignore($this->pre_register->visitor_id)];
        } else {
            $email    = ['nullable','email', 'string', 'unique:visitors,email'];
            $phone    = ['required', 'string', 'numeric', 'regex:/^[0-9]/', 'unique:visitors,phone'];
            $national_identification_no    = ['required','string', 'max:100', 'unique:visitors,national_identification_no'];
        }
        return [
            'first_name'                 => 'required|string|max:100',
            'last_name'                  => 'required|string|max:100',
            'email'                      => $email,
            'phone'                      => $phone,
            'employee_id'                => 'required|numeric',
            'gender'                     => 'required|numeric',
            'national_identification_no' => $national_identification_no,
            'expected_date'              => 'required',
            'expected_time'              => 'required',
            'comment'                    => 'nullable|max:191',
            'address'                    => 'nullable|max:191',
        ];
    }
}
