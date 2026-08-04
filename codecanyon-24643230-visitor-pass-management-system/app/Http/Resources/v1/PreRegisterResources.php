<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PreRegisterResources extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id"                         => $this->id,
            "name"                       => $this->visitor->name,
            "first_name"                 => $this->visitor->first_name,
            "last_name"                  => $this->visitor->last_name,
            "email"                      => $this->visitor->email,
            "phone"                      => $this->visitor->phone,
            "country_code"               => $this->visitor->country_code,
            "country_code_name"          => $this->visitor->country_code_name,
            "gender"                     => trans('genders.' . $this->visitor->gender),
            "gender_id"                  => $this->visitor->gender,
            "national_identification_no" => $this->visitor->national_identification_no,
            "address"                    => blank($this->visitor->address) ? "" : $this->visitor->address,
            "expected_date"              => date('Y-m-d', strtotime($this->expected_date)),
            "expected_time"              => date('h:i A', strtotime($this->expected_time)),
            "employee_name"              => !blank($this->employee) ? $this->employee->name : '',
            "employeeID"                 => !blank($this->employee) ? $this->employee->id : '',
            "image"                      => asset('assets/img/default/user.png'),
            "comment"                    => blank($this->comment) ? "" : $this->comment,
            "status"                     => trans('statuses.' . $this->visitor->status),
            "raw_expected_date"          => $this->expected_date,
            "raw_expected_time"          => date('H:i', strtotime($this->expected_time)),

        ];
    }
}
