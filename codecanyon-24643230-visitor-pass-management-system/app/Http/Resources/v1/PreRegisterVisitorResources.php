<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PreRegisterVisitorResources extends JsonResource
{

    public function toArray($request)
    {
        return [
            "id"                         => $this->id,
            "name"                       => $this->name,
            "first_name"                 => $this->first_name,
            "last_name"                  => $this->last_name,
            "email"                      => $this->email,
            "phone"                      => $this->phone,
            "country_code"               => $this->country_code,
            "country_code_name"          => $this->country_code_name,
            "gender"                     => trans('genders.' . $this->gender),
            "gender_id"                     => $this->gender,
            "national_identification_no" => $this->national_identification_no,
            "address"                    => blank($this->address) ? "" : $this->address,
            "expected_date"              => date('Y-m-d', strtotime($this->preregister->expected_date)),
            "expected_time"              => date('h:i A', strtotime($this->preregister->expected_time)),
            "employee_name"              => !blank($this->preregister->employee) ? $this->preregister->employee->name : '',
            "employeeID"                 => !blank($this->preregister->employee) ? $this->preregister->employee->id : '',
            "image"                      => asset('assets/img/default/user.png'),
            "comment"                    => blank($this->preregister->comment) ? "" : $this->preregister->comment,
            "status"                     => trans('statuses.' . $this->status),
            "raw_expected_date"              => $this->preregister->expected_date,
            "raw_expected_time"              => date('H:i', strtotime($this->preregister->expected_time)),

        ];
    }
}
