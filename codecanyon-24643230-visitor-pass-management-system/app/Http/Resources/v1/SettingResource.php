<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    public function toArray($request)
    {

        return [
            'site_email'               => $this['site_email'],
            'site_name'                => $this['site_name'],
            'site_phone_number'        => $this['site_phone_number'],
            'site_logo'                => asset("images/" . setting('site_logo')),
            'site_footer'              => $this['site_footer'],
            'front_end_enable_disable' => $this['front_end_enable_disable'],
            'photo_capture_enable'     => $this['photo_capture_enable'],
            'site_address'             => strip_tags($this['site_address']),
            'site_description'         => strip_tags($this['site_description']),
            'welcome_screen'           => strip_tags($this['welcome_screen']) ?? "",
            'terms_condition'          => strip_tags($this['terms_condition']) ?? "",
        
        ];
    }
}
