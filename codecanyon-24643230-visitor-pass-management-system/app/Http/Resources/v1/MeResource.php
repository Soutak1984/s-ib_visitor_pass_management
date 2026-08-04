<?php

/**
 * Created by PhpStorm.
 * User: dipok
 * Date: 19/4/20
 * Time: 4:10 PM
 */

namespace App\Http\Resources\v1;

use App\Models\Order;
use Illuminate\Http\Resources\Json\JsonResource;

class MeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'name'              => $this->name,
            'email'             => $this->email,
            'username'          => $this->username,
            'phone'             => $this->phone,
            'country_code'      => $this->country_code,
            'country_code_name' => $this->country_code_name,
            'address'           => blank($this->address) ? "" : $this->address,
            'department'        => blank($this->department) ? "" : $this->department->name,
            'image'             => $this->images,
        ];
    }
}
